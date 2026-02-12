<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TokenFactus extends Model
{
    protected $table = 'tokens_factus';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    // Scopes
    public function scopeVigentes($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeExpirados($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeMasReciente($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Accessors
    public function getEstaVigenteAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isFuture();
    }

    public function getEstaExpiradoAttribute(): bool
    {
        return !$this->esta_vigente;
    }

    public function getTiempoRestanteAttribute(): ?int
    {
        if (!$this->esta_vigente) {
            return 0;
        }
        
        return now()->diffInSeconds($this->expires_at);
    }

    public function getMinutosRestantesAttribute(): ?int
    {
        if (!$this->tiempo_restante) {
            return 0;
        }
        
        return (int) floor($this->tiempo_restante / 60);
    }

    public function getDebeRenovarseAttribute(): bool
    {
        // Renovar si expira en menos de 5 minutos
        if (!$this->esta_vigente) {
            return true;
        }
        
        return $this->minutos_restantes < 5;
    }

    // Métodos
    public static function obtenerTokenVigente(): ?self
    {
        return self::vigentes()
                   ->masReciente()
                   ->first();
    }

    public static function guardarNuevoToken(string $accessToken, ?string $refreshToken, int $expiresIn): self
    {
        return self::create([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => now()->addSeconds($expiresIn),
        ]);
    }

    public static function limpiarTokensExpirados(): int
    {
        // Mantener solo los últimos 10 tokens (incluso expirados) para historial
        $mantener = self::masReciente()->limit(10)->pluck('id');
        
        return self::whereNotIn('id', $mantener)->delete();
    }
}