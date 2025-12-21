<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'judul',
        'deskripsi',
        'ministry_id',
        'user_id',
        'status_id',
        'keterangan',
        'tanggal_pengajuan',
        'file_path',
    ];

    /**
     * Get the kementerian associated with the proposal.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }
    public function status()
{
    return $this->belongsTo(Status::class);
}
}
