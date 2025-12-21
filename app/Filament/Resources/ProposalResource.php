<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Proposal;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ProposalResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ProposalResource\RelationManagers;
use Illuminate\Support\Facades\Storage;

class ProposalResource extends Resource
{
    protected static ?string $model = Proposal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Proposals';
    protected static ?string $navigationGroup = 'Manajemen Proposal';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Proposal')
                    ->schema([
                        Forms\Components\TextInput::make('judul')
                            ->required()
                            ->maxLength(150)
                            ->label('Judul Proposal'),
                        Forms\Components\Textarea::make('deskripsi')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull()
                            ->label('Deskripsi'),
                        Forms\Components\Select::make('ministry_id')
                            ->relationship('ministry', 'nama', function ($query) {
                                $user = auth()->user();
                                // Role tertinggi bisa pilih semua ministry
                                if ($user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara'])) {
                                    return $query;
                                }
                                // Menteri dan Anggota hanya bisa pilih ministry mereka sendiri
                                if ($user->ministry_id) {
                                    return $query->where('id', $user->ministry_id);
                                }
                                return $query->where('id', 0); // Tidak bisa pilih apa-apa
                            })
                            ->label('Kementerian')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(function () {
                                $user = auth()->user();
                                // Auto-set ke ministry user jika bukan role tertinggi
                                if (!$user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara']) && $user->ministry_id) {
                                    return $user->ministry_id;
                                }
                                return null;
                            }),
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('Pengaju')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(auth()->id()),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('File & Status')
                    ->schema([
                        Forms\Components\Select::make('status_id')
                            ->relationship('status', 'name')
                            ->required()
                            ->label('Status')
                            ->searchable()
                            ->preload()
                            ->default(function () {
                                $user = auth()->user();
                                // Jika user adalah Anggota, default ke "pending_menteri" (Review Menteri)
                                if ($user->hasRole('Anggota')) {
                                    $status = \App\Models\Status::where('name', 'pending_menteri')->first();
                                    return $status ? $status->id : null;
                                }
                                return null;
                            })
                            ->disabled(function ($record) {
                                $user = auth()->user();
                                // Anggota tidak bisa mengubah status setelah proposal dibuat
                                if ($user->hasRole('Anggota') && $record && $record->exists) {
                                    return true; // Disable untuk Anggota jika proposal sudah ada
                                }
                                return false;
                            }),
                        Forms\Components\DatePicker::make('tanggal_pengajuan')
                            ->required()
                            ->default(now())
                            ->label('Tanggal Pengajuan'),
                        FileUpload::make('file_path')
                            ->required()
                            ->label('File Proposal')
                            ->preserveFilenames()
                            ->disk('public')
                            ->directory('proposals')
                            ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                            ->maxSize(5120),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan/Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Tambahkan catatan atau keterangan jika diperlukan (contoh: alasan revisi, komentar dari reviewer, dll)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->date('d M Y')
                    ->label('Tanggal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('judul')
                    ->searchable()
                    ->wrap()
                    ->limit(40),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pengaju')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ministry.nama')
                    ->label('Kementerian')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\SelectColumn::make('status_id')
                    ->label('Status')
                    ->options(function () {
                        return \App\Models\Status::all()->mapWithKeys(function ($status) {
                            $label = match ($status->name) {
                                'pending_menteri' => '⏳ Review Menteri',
                                'pending_sekretaris' => '⏳ Review Sekretaris',
                                'pending_bendahara' => '⏳ Review Bendahara',
                                'pending_wakil_presiden' => '⏳ Review Wakil Presiden',
                                'pending_presiden' => '⏳ Review Presiden',
                                'approved' => '✅ Disetujui',
                                'rejected' => '❌ Ditolak',
                                'revisi' => '📝 Perlu Revisi',
                                default => $status->name,
                            };
                            return [$status->id => $label];
                        });
                    })
                    ->disabled(function ($record) {
                        $user = auth()->user();
                        
                        // Pimpinan (Super Admin, Presiden BEM, Wakil Presiden BEM, Sekretaris, Bendahara, Menteri) selalu bisa mengubah
                        if ($user->hasAnyRole(['Super Admin', 'Presiden BEM', 'Wakil Presiden BEM', 'Sekretaris', 'Bendahara', 'Menteri'])) {
                            return false; // Enable dropdown
                        }
                        
                        // Anggota TIDAK BISA mengubah status setelah proposal sudah dibuat/dikirim
                        if ($user->hasRole('Anggota')) {
                            if ($record && $record->exists) {
                                return true; // Disable dropdown untuk Anggota setelah proposal dibuat
                            }
                        }
                        
                        return false; // Default: enable dropdown
                    })
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->wrap()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('file_path')
                    ->label('File')
                    ->icon(fn ($record) => 'heroicon-o-document-text')
                    ->url(function ($record) {
                        if (!$record->file_path) {
                            return null;
                        }
                        // Gunakan Storage::url untuk mendapatkan URL yang benar
                        return url(Storage::url($record->file_path));
                    })
                    ->openUrlInNewTab()
                    ->tooltip('Klik untuk membuka dokumen'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_id')
                    ->relationship('status', 'name')
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('ministry_id')
                    ->relationship('ministry', 'nama')
                    ->label('Kementerian'),
                Tables\Filters\Filter::make('tanggal_pengajuan')
                    ->form([
                        Forms\Components\DatePicker::make('dari')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('sampai')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pengajuan', '>=', $date),
                            )
                            ->when(
                                $data['sampai'],
                                fn (Builder $query, $date): Builder => $query->whereDate('tanggal_pengajuan', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('tanggal_pengajuan', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProposals::route('/'),
            'create' => Pages\CreateProposal::route('/create'),
            'edit' => Pages\EditProposal::route('/{record}/edit'),
        ];
    }
}
