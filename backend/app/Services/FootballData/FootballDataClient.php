<?php

namespace App\Services\FootballData;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FootballDataClient
{
    public function __construct(
        protected ?string $baseUrl,
        protected ?string $token,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->token) && filled($this->baseUrl);
    }

    public function fetchCompetitions(): array
    {
        return $this->request('/competitions');
    }

    public function fetchCompetition(int $competitionId): array
    {
        return $this->request('/competitions/'.$competitionId);
    }

    public function fetchCompetitionTeams(int $competitionId): array
    {
        return $this->request('/competitions/'.$competitionId.'/teams');
    }

    protected function request(string $uri): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('FOOTBALL_DATA_TOKEN no está configurado.', 503);
        }

        $cacheKey = 'football_data:'.md5($uri);

        try {
            return Cache::lock('football_data_request_lock:'.$cacheKey, 30)->block(12, function () use ($cacheKey, $uri) {
                return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($uri) {
                    $this->reserveRateLimitSlot();

                    try {
                        $response = Http::withHeaders(['X-Auth-Token' => $this->token])
                            ->acceptJson()
                            ->timeout(10)
                            ->get(rtrim($this->baseUrl, '/').$uri);
                    } catch (ConnectionException $exception) {
                        throw new RuntimeException('No se pudo conectar con football-data.org.', 503, $exception);
                    }

                    if ($response->status() === 429) {
                        throw new RuntimeException('Se superó el límite de peticiones de football-data.org.', 429);
                    }

                    if ($response->status() === 401) {
                        throw new RuntimeException('football-data.org rechazó la credencial configurada.', 503);
                    }

                    if ($response->status() === 403) {
                        throw new RuntimeException('La suscripción de football-data.org no permite consultar este recurso.', 503);
                    }

                    if ($response->status() === 404) {
                        throw new RuntimeException('football-data.org no encontró el recurso solicitado.', 503);
                    }

                    if ($response->failed() || ! is_array($response->json())) {
                        throw new RuntimeException('La integración con football-data.org falló.', 503);
                    }

                    return $response->json();
                });
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException('La integración con football-data.org está ocupada; inténtalo nuevamente.', 503, $exception);
        }
    }

    protected function reserveRateLimitSlot(): void
    {
        try {
            Cache::lock('football_data_rate_limit_lock', 10)->block(5, function (): void {
                $this->enforceRateLimit();
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException('No se pudo verificar la cuota de football-data.org; inténtalo nuevamente.', 503, $exception);
        }
    }

    protected function enforceRateLimit(): void
    {
        $key = 'football_data_rate_limit';
        $bucket = Cache::get($key, ['count' => 0, 'window_start' => time()]);

        $now = time();
        if ($now - ($bucket['window_start'] ?? $now) >= 60) {
            $bucket = ['count' => 0, 'window_start' => $now];
        }

        if (($bucket['count'] ?? 0) >= 10) {
            throw new RuntimeException('Se excedió el límite de 10 peticiones por minuto.', 429);
        }

        $bucket['count'] = ($bucket['count'] ?? 0) + 1;
        Cache::put($key, $bucket, 120);
    }
}
