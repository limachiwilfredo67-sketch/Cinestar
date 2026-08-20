<?php declare(strict_types=1);

final class VimeusSdk {
    private $apiKey;
    private $baseUrl;
    private $timeout;

    public function __construct(string $apiKey, string $baseUrl = 'https://vimeus.com', int $timeout = 15) {
        if ($apiKey === '') throw new \InvalidArgumentException('API key must not be empty.');
        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    private function request(string $method, string $path, array $query = []): array {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if (!empty($query)) $url .= '?' . http_build_query($query);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'X-API-Key: ' . $this->apiKey,
                'User-Agent: VimeusSdk-PHP/1.0'
            ]
        ]);
        
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) throw new \RuntimeException('HTTP Error ' . $status);
        
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function listMovies(int $page = 1): array {
        return $this->request('GET', '/api/listing/movies', ['page' => $page]);
    }

    public function listSeries(int $page = 1): array {
        return $this->request('GET', '/api/listing/series', ['page' => $page]);
    }

    public function listAnimes(int $page = 1): array {
        return $this->request('GET', '/api/listing/animes', ['page' => $page]);
    }
}
