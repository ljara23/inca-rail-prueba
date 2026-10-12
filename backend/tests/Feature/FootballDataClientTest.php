<?php

namespace Tests\Feature;

use App\Services\FootballData\FootballDataClient;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FootballDataClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_reaches_the_rate_limit_after_ten_requests(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        $cacheKey = 'football_data:'.md5('/competitions');

        Http::fake([
            '*' => Http::response(['competitions' => [['id' => 1, 'name' => 'Premier League']]], 200),
        ]);

        for ($i = 0; $i < 10; $i++) {
            Cache::forget($cacheKey);
            $client->fetchCompetitions();
        }

        Cache::forget($cacheKey);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Se excedió el límite de 10 peticiones por minuto.');
        $this->expectExceptionCode(429);

        $client->fetchCompetitions();
    }

    public function test_it_throws_a_server_error_when_the_provider_fails(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        $cacheKey = 'football_data:'.md5('/competitions');

        Http::fake([
            '*' => Http::response([], 503),
        ]);

        Cache::forget($cacheKey);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La integración con football-data.org falló.');
        $this->expectExceptionCode(503);

        $client->fetchCompetitions();
    }

    public function test_it_maps_connection_failures_to_a_service_unavailable_error(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        Http::fake([
            '*' => Http::failedConnection(),
        ]);

        try {
            $client->fetchCompetitions();
            $this->fail('A connection failure should be reported as unavailable.');
        } catch (RuntimeException $exception) {
            $this->assertSame(503, $exception->getCode());
            $this->assertSame('No se pudo conectar con football-data.org.', $exception->getMessage());
        }
    }

    public function test_it_caches_successful_responses_to_avoid_redundant_provider_requests(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        Http::fake([
            '*' => Http::response(['competitions' => [['id' => 1, 'name' => 'Premier League']]], 200),
        ]);

        $firstResponse = $client->fetchCompetitions();
        $secondResponse = $client->fetchCompetitions();

        $this->assertSame($firstResponse, $secondResponse);
        Http::assertSentCount(1);
    }

    public function test_it_sends_the_provider_token_in_the_expected_header(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        Http::fake([
            '*' => Http::response(['competitions' => []], 200),
        ]);

        $client->fetchCompetitions();

        Http::assertSent(fn (ClientRequest $request) => $request->hasHeader('X-Auth-Token', 'secret-token'));
    }

    public function test_it_fetches_teams_for_a_competition(): void
    {
        $client = new FootballDataClient('https://api.football-data.org/v4', 'secret-token');
        Http::fake([
            '*' => Http::response(['teams' => [['id' => 1, 'name' => 'Arsenal FC']]], 200),
        ]);

        $payload = $client->fetchCompetitionTeams(2021);

        $this->assertSame('Arsenal FC', $payload['teams'][0]['name']);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/competitions/2021/teams'));
    }
}
