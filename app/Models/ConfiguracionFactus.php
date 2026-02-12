<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ConfiguracionFactus extends Model
{
    protected $table = 'configuracion_factus';

    protected $fillable = [
        'client_id',
        'client_secret',
        'username',
        'password_encrypted',
        'ambiente',
        'url_api',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'password_encrypted',
        'client_secret',
    ];

    // Scopes
    public function scopeActiva($query)
    {
        return $query->where('activo', true);
    }

    public function scopeSandbox($query)
    {
        return $query->where('ambiente', 'sandbox');
    }

    public function scopeProduccion($query)
    {
        return $query->where('ambiente', 'produccion');
    }

    // Accessors
    public function getPasswordAttribute(): ?string
    {
        if (!$this->password_encrypted) {
            return null;
        }
        
        try {
            return Crypt::decryptString($this->password_encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Mutators
    public function setPasswordAttribute($value): void
    {
        if ($value) {
            $this->attributes['password_encrypted'] = Crypt::encryptString($value);
        }
    }

    public function getEsSandboxAttribute(): bool
    {
        return $this->ambiente === 'sandbox';
    }

    public function getEsProduccionAttribute(): bool
    {
        return $this->ambiente === 'produccion';
    }

    // Métodos
    public function getCredenciales(): array
    {
        return [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }
}