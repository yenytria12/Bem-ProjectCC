<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = 'Activity Log';
    
    protected static ?string $navigationGroup = 'Manajemen Sistem';
    
    protected static ?int $navigationSort = 99;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Log')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->label('User')
                            ->disabled(),
                        Forms\Components\Select::make('activity_type')
                            ->options([
                                'login' => 'Login',
                                'logout' => 'Logout',
                                'create' => 'Create',
                                'update' => 'Update',
                                'delete' => 'Delete',
                                'view' => 'View',
                            ])
                            ->label('Tipe Aktivitas')
                            ->disabled(),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('model_type')
                            ->label('Model Type')
                            ->disabled(),
                        Forms\Components\TextInput::make('model_id')
                            ->label('Model ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('IP Address')
                            ->disabled(),
                        Forms\Components\Textarea::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('changes')
                            ->label('Perubahan')
                            ->disabled()
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadata')
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Tipe Aktivitas')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'login' => 'success',
                        'logout' => 'danger',
                        'create' => 'warning',
                        'update' => 'info',
                        'delete' => 'gray',
                        'view' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'login' => '🔐 Login',
                        'logout' => '🚪 Logout',
                        'create' => '➕ Create',
                        'update' => '✏️ Update',
                        'delete' => '🗑️ Delete',
                        'view' => '👁️ View',
                        default => $state,
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Model')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Tipe Aktivitas')
                    ->options([
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'view' => 'View',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'name')
                    ->label('User'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        // Hanya tampilkan di navigation jika user punya permission view_any_activity_log
        return auth()->check() && auth()->user()->can('view_any_activity_log');
    }
    
    public static function canViewAny(): bool
    {
        // Hanya user dengan permission view_any_activity_log yang bisa akses
        return auth()->check() && auth()->user()->can('view_any_activity_log');
    }
    
    public static function canView($record): bool
    {
        // Hanya user dengan permission view_activity_log yang bisa view
        return auth()->check() && auth()->user()->can('view_activity_log');
    }
    
    public static function canCreate(): bool
    {
        return false; // Log tidak bisa dibuat manual
    }
    
    public static function canEdit($record): bool
    {
        return false; // Log tidak bisa di-edit
    }
    
    public static function canDelete($record): bool
    {
        // Hanya Super Admin yang bisa delete log (tidak ada permission delete untuk log)
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
    
    public static function canDeleteAny(): bool
    {
        // Hanya Super Admin yang bisa delete log (tidak ada permission delete untuk log)
        return auth()->check() && auth()->user()->hasRole('Super Admin');
    }
}
