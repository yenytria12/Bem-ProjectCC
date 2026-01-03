<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser; // <--- PENTING BUAT FILAMENT
use Filament\Panel; // <--- PENTING BUAT FILAMENT
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

// Perhatikan: implements nambah FilamentUser
class User extends Authenticatable implements JWTSubject, FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasApiTokens;

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'ministry_id',
        'google_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function ministry()
    {
        return $this->belongsTo(Ministry::class);
    }

    public function programKerjas()
    {
        return $this->hasMany(ProgramKerja::class);
    }

    // =================================================================
    //  INI KUNCI BIAR BISA LOGIN DI AZURE (PRODUCTION)
    // =================================================================
    public function canAccessPanel(Panel $panel): bool
    {
        // Semua user yang terdaftar bisa akses admin panel
        // Akses ke fitur tertentu dikontrol via Filament Shield/Policies
        return true;
    }
}