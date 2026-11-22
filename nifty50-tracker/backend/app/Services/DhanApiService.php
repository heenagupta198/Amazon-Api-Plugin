<?php

namespace App\Services;

use App\Models\ApiLog;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DhanApiService
{
    public function isConfigured(): bool
    {
        return filled(config('dhan.access_token')) && filled(config('dhan.client_id'));
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(): array
    {
        return $this->get('/profile');
    }

    /**
     * @return array<string, mixed>
     */
    public function connectionTest(): array
    {
        if (! $this->isConfigured()) {
            return [
                'connected' => false,
                'configured' => false,
                'message' => 'Set DHAN_ACCESS_TOKEN and DHAN_CLIENT_ID in backend/.env',
            ];
        }

        try {
            $profile = $this->profile();

            return [
                'connected' => true,
                'configured' => true,
                'data_plan' => $profile['dataPlan'] ?? null,
                'token_validity' => $profile['tokenValidity'] ?? null,
                'message' => 'Dhan profile API responded successfully',
            ];
        } catch (RuntimeException $e) {
            return [
                'connected' => false,
                'configured' => true,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<int|string, int|string>  $securityIds
     * @return array<string, mixed>
     */
    public function marketOhlc(array $securityIds, string $segment = 'NSE_EQ'): array
    {
        $payload = [$segment => array_values(array_map('intval', $securityIds))];

        return $this->post('/marketfeed/ohlc', $payload);
    }

    /**
     * @param  array<string, array<int, int>>  $segments
     * @return array<string, mixed>
     */
    public function marketOhlcBySegment(array $segments): array
    {
        $normalized = [];
        foreach ($segments as $segment => $ids) {
            $normalized[$segment] = array_values(array_map('intval', $ids));
        }

        return $this->post('/marketfeed/ohlc', $normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $path): array
    {
        $response = Http::withHeaders($this->headers())
            ->acceptJson()
            ->get($this->url($path));

        return $this->decode($response->status(), $path, $response->json(), $response->body());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(string $path, array $payload): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->acceptJson()
                ->asJson()
                ->post($this->url($path), $payload);
        } catch (RequestException $e) {
            $this->log($path, $e->response?->status() ?? 0, $e->getMessage());

            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        return $this->decode($response->status(), $path, $response->json(), $response->body());
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(int $status, string $path, mixed $json, string $body): array
    {
        if ($status < 200 || $status >= 300) {
            $message = is_array($json) ? ($json['remarks'] ?? $json['message'] ?? $body) : $body;
            $this->log($path, $status, (string) $message, ['body' => $json]);

            throw new RuntimeException("Dhan API error ({$status}) on {$path}: {$message}");
        }

        return is_array($json) ? $json : ['raw' => $body];
    }

    /**
     * @return array<string, string>
     */
    private function headers(): array
    {
        return [
            'access-token' => (string) config('dhan.access_token'),
            'client-id' => (string) config('dhan.client_id'),
        ];
    }

    private function url(string $path): string
    {
        return rtrim((string) config('dhan.base_url'), '/').'/'.ltrim($path, '/');
    }

  private function log(string $endpoint, int $status, string $message, ?array $context = null): void
    {
        ApiLog::query()->create([
            'provider' => 'dhan',
            'endpoint' => $endpoint,
            'status_code' => $status ?: null,
            'message' => $message,
            'context' => $context,
        ]);
    }
}