<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens_factus', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_factus');
    }
};
// ```

// ---

// ## ⚠️ ANTES DE MIGRAR - VERIFICAR USERS

// Abre tu carpeta `database/migrations` y dime:

// **¿Ya existe alguna migración de `users` de las que venían por defecto en Laravel?**
// ```
// database/migrations/
// ├── 0001_01_01_000000_create_users_table.php  ← ¿Existe esta?
// ├── 0001_01_01_000001_create_cache_table.php  ← ¿Existe esta?
// ├── 0001_01_01_000002_create_jobs_table.php   ← ¿Existe esta?
// └── ... tus nuevas migraciones