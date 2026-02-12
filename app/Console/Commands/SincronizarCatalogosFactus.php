<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FactusService;
use Illuminate\Support\Facades\DB;

class SincronizarCatalogosFactus extends Command
{
    protected $signature = 'factus:sincronizar {--rangos : Solo sincronizar rangos de numeración} {--municipios : Solo sincronizar municipios} {--tributos : Solo sincronizar tributos} {--unidades : Solo sincronizar unidades de medida}';

    protected $description = 'Sincronizar catálogos desde Factus API (rangos, municipios, tributos, unidades)';

    protected FactusService $factusService;

    public function __construct(FactusService $factusService)
    {
        parent::__construct();
        $this->factusService = $factusService;
    }

    public function handle()
    {
        $this->info('🔄 Iniciando sincronización con Factus API...');
        $this->newLine();

        try {
            // Si no hay opciones, sincronizar todo
            $sincronizarTodo = !$this->option('rangos') 
                && !$this->option('municipios') 
                && !$this->option('tributos') 
                && !$this->option('unidades');

            if ($sincronizarTodo || $this->option('municipios')) {
                $this->sincronizarMunicipios();
            }

            if ($sincronizarTodo || $this->option('tributos')) {
                $this->sincronizarTributos();
            }

            if ($sincronizarTodo || $this->option('unidades')) {
                $this->sincronizarUnidadesMedida();
            }

            if ($sincronizarTodo || $this->option('rangos')) {
                $this->sincronizarRangosNumeracion();
            }

            $this->newLine();
            $this->info('✅ Sincronización completada exitosamente!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error en la sincronización: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function sincronizarMunicipios()
    {
        $this->info('📍 Sincronizando municipios...');

        $response = $this->factusService->getMunicipios();

        if (!$response->successful()) {
            throw new \Exception('Error al obtener municipios: ' . $response->body());
        }

        $data = $response->json();
        $municipios = $data['data'] ?? [];

        $bar = $this->output->createProgressBar(count($municipios));
        $bar->start();

        $insertados = 0;
        $actualizados = 0;

        foreach ($municipios as $municipio) {
            $existe = DB::table('municipios')
                ->where('codigo_dian', $municipio['code'])
                ->exists();

            if ($existe) {
                DB::table('municipios')
                    ->where('codigo_dian', $municipio['code'])
                    ->update([
                        'nombre' => $municipio['name'],
                        'departamento' => $municipio['department'],
                        'updated_at' => now(),
                    ]);
                $actualizados++;
            } else {
                DB::table('municipios')->insert([
                    'codigo_dian' => $municipio['code'],
                    'nombre' => $municipio['name'],
                    'departamento' => $municipio['department'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertados++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Insertados: {$insertados} | Actualizados: {$actualizados}");
    }

    protected function sincronizarTributos()
    {
        $this->info('💰 Sincronizando tributos de productos...');

        $response = $this->factusService->getTributos();

        if (!$response->successful()) {
            throw new \Exception('Error al obtener tributos: ' . $response->body());
        }

        $data = $response->json();
        $tributos = $data['data'] ?? [];

        $bar = $this->output->createProgressBar(count($tributos));
        $bar->start();

        $insertados = 0;
        $actualizados = 0;

        foreach ($tributos as $tributo) {
            $existe = DB::table('tributos')
                ->where('codigo_dian', $tributo['code'])
                ->exists();

            if ($existe) {
                DB::table('tributos')
                    ->where('codigo_dian', $tributo['code'])
                    ->update([
                        'nombre' => $tributo['name'],
                        'descripcion' => $tributo['description'] ?? null,
                        'updated_at' => now(),
                    ]);
                $actualizados++;
            } else {
                DB::table('tributos')->insert([
                    'codigo_dian' => $tributo['code'],
                    'nombre' => $tributo['name'],
                    'descripcion' => $tributo['description'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertados++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Insertados: {$insertados} | Actualizados: {$actualizados}");
    }

    protected function sincronizarUnidadesMedida()
    {
        $this->info('📏 Sincronizando unidades de medida...');

        $response = $this->factusService->getUnidadesMedida();

        if (!$response->successful()) {
            throw new \Exception('Error al obtener unidades de medida: ' . $response->body());
        }

        $data = $response->json();
        $unidades = $data['data'] ?? [];

        $bar = $this->output->createProgressBar(count($unidades));
        $bar->start();

        $insertados = 0;
        $actualizados = 0;

        foreach ($unidades as $unidad) {
            $existe = DB::table('unidades_medida')
                ->where('codigo_dian', $unidad['code'])
                ->exists();

            if ($existe) {
                DB::table('unidades_medida')
                    ->where('codigo_dian', $unidad['code'])
                    ->update([
                        'nombre' => $unidad['name'],
                        'updated_at' => now(),
                    ]);
                $actualizados++;
            } else {
                DB::table('unidades_medida')->insert([
                    'codigo_dian' => $unidad['code'],
                    'nombre' => $unidad['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $insertados++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("   ✓ Insertados: {$insertados} | Actualizados: {$actualizados}");
    }

    protected function sincronizarRangosNumeracion()
{
    $this->info('🔢 Sincronizando rangos de numeración...');

    $response = $this->factusService->getRangosNumeracion();

    if (!$response->successful()) {
        throw new \Exception('Error al obtener rangos: ' . $response->body());
    }

    $data = $response->json();
    $rangos = $data['data']['data'] ?? [];

    $bar = $this->output->createProgressBar(count($rangos));
    $bar->start();

    $insertados = 0;
    $actualizados = 0;

    foreach ($rangos as $rango) {
        // Buscar por prefijo + document
        $existe = DB::table('rangos_numeracion')
            ->where('prefijo', $rango['prefix'])
            ->where('document', $rango['document'])
            ->exists();

        $rangoData = [
            'document' => $rango['document'],
            'prefijo' => $rango['prefix'],
            'desde' => $rango['from'],                    // ✅ Puede ser null
            'hasta' => $rango['to'],                      // ✅ Puede ser null
            'consecutivo_actual' => $rango['current'] ?? 0,
            'numero_resolucion' => $rango['resolution_number'],
            'fecha_inicio' => $rango['start_date'],       // ✅ Puede ser null
            'fecha_fin' => $rango['end_date'],            // ✅ Puede ser null
            'technical_key' => $rango['technical_key'],
            'is_expired' => $rango['is_expired'] ?? false,
            'is_active' => $rango['is_active'] ?? 1,
            'updated_at' => now(),
        ];

        if ($existe) {
            DB::table('rangos_numeracion')
                ->where('prefijo', $rango['prefix'])
                ->where('document', $rango['document'])
                ->update($rangoData);
            $actualizados++;
        } else {
            $rangoData['created_at'] = now();
            DB::table('rangos_numeracion')->insert($rangoData);
            $insertados++;
        }

        $bar->advance();
    }

    $bar->finish();
    $this->newLine();
    $this->line("   ✓ Insertados: {$insertados} | Actualizados: {$actualizados}");
}
}