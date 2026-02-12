<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class FactusService
{
    protected string $baseUrl;
    protected array $credentials;
    protected int $timeout;

    public function __construct()
    {
        $environment = config('factus.environment');
        $this->baseUrl = config("factus.urls.{$environment}");
        $this->credentials = config('factus.credentials');
        $this->timeout = config('factus.timeout', 30);
    }

    /**
     * Obtener URL base según ambiente
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Verificar si estamos en ambiente de pruebas
     */
    public function isSandbox(): bool
    {
        return config('factus.environment') === 'sandbox';
    }

    /**
     * Obtener token de acceso (con caché)
     */
    public function getAccessToken(): string
    {
        $cacheKey = config('factus.token.cache_key');
        $expiresKey = config('factus.token.expires_cache_key');

        // Verificar si hay token en caché y no está por expirar
        $token = Cache::get($cacheKey);
        $expiresAt = Cache::get($expiresKey);

        if ($token && $expiresAt) {
            $expiresAtCarbon = Carbon::parse($expiresAt);
            $refreshBefore = config('factus.token.refresh_before', 300);

            // Si el token expira en más de 5 minutos, usarlo
            if ($expiresAtCarbon->diffInSeconds(now()) > $refreshBefore) {
                return $token;
            }
        }

        // Si no hay token o está por expirar, obtener uno nuevo
        return $this->authenticate();
    }

    /**
     * Autenticar y obtener token
     */
    public function authenticate(): string
    {
        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/oauth/token", [
                    'grant_type' => 'password',
                    'client_id' => $this->credentials['client_id'],
                    'client_secret' => $this->credentials['client_secret'],
                    'username' => $this->credentials['username'],
                    'password' => $this->credentials['password'],
                ]);

            if (!$response->successful()) {
                throw new Exception('Error al autenticar con Factus: ' . $response->body());
            }

            $data = $response->json();
            $accessToken = $data['access_token'];
            $refreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 3600;

            // Guardar en caché
            $expiresAt = now()->addSeconds($expiresIn);

            Cache::put(
                config('factus.token.cache_key'),
                $accessToken,
                $expiresAt
            );

            if ($refreshToken) {
                Cache::put(
                    config('factus.token.refresh_cache_key'),
                    $refreshToken,
                    $expiresAt
                );
            }

            Cache::put(
                config('factus.token.expires_cache_key'),
                $expiresAt->toDateTimeString(),
                $expiresAt
            );

            Log::info('Token de Factus obtenido exitosamente', [
                'expires_at' => $expiresAt->toDateTimeString()
            ]);

            return $accessToken;

        } catch (Exception $e) {
            Log::error('Error al autenticar con Factus', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Realizar request autenticado a Factus
     */
    protected function request(string $method, string $endpoint, array $data = [])
    {
        $token = $this->getAccessToken();

        $request = Http::timeout($this->timeout)
            ->withToken($token)
            ->accept('application/json');

        $url = "{$this->baseUrl}{$endpoint}";

        $response = match(strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => throw new Exception("Método HTTP no soportado: {$method}")
        };

        if (!$response->successful()) {
            Log::error('Error en request a Factus', [
                'method' => $method,
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
        }

        return $response;
    }

    /**
     * Métodos GET
     */
    public function get(string $endpoint, array $params = [])
    {
        return $this->request('GET', $endpoint, $params);
    }

    /**
     * Métodos POST
     */
    public function post(string $endpoint, array $data = [])
    {
        return $this->request('POST', $endpoint, $data);
    }

    /**
     * Obtener rangos de numeración
     */
    public function getRangosNumeracion()
    {
        return $this->get('/v1/numbering-ranges');
    }

    /**
     * Obtener municipios
     */
    public function getMunicipios()
    {
        return $this->get('/v1/municipalities');
    }

    /**
     * Obtener tributos
     */
    public function getTributos()
    {
        return $this->get('/v1/tributes/products');
        // https://api-sandbox.factus.com.co/v1/tributes/products?name=
    }

    /**
     * Obtener unidades de medida
     */
    public function getUnidadesMedida()
    {
        return $this->get('/v1/measurement-units');
        // https://api-sandbox.factus.com.co/v1/measurement-units
    }

    /**
     * Crear factura electrónica
     */
    public function crearFactura(array $facturaData)
    {
        return $this->post('/v1/bills/validate', $facturaData);
    }

    /**
     * Consultar estado de factura
     */
    public function consultarFactura(string $referenceCode)
    {
        return $this->get("/v1/bills/{$referenceCode}");
    }

    /**
     * Test de conexión
     */
    public function testConnection(): bool
    {
        try {
            $token = $this->getAccessToken();
            return !empty($token);
        } catch (Exception $e) {
            return false;
        }
    }
}