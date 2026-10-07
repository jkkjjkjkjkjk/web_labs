<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;

class ApiClient {
    public const PRODUCTS_URL = 'https://dummyjson.com/products/category/smartphones';
    public const CACHE_FILE   = __DIR__ . '/api_cache.json';
    public const CACHE_TTL    = 300; // 5 минут

    private Client $client;

    public function __construct() {
        $this->client = new Client(['timeout' => 5]);
    }

    public function request(string $url): array {
        try {
            $response = $this->client->get($url);
            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (!is_array($data)) {
                return ['error' => 'API вернуло некорректный ответ'];
            }
            return $data;
        } catch (\Exception $e) {
            return ['error' => 'API недоступно: ' . $e->getMessage()];
        }
    }

    public function getProducts(bool $force = false): array {
        if (!$force && file_exists(self::CACHE_FILE) && time() - filemtime(self::CACHE_FILE) < self::CACHE_TTL) {
            $cached = json_decode(file_get_contents(self::CACHE_FILE), true);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $data = $this->request(self::PRODUCTS_URL);

        if (!isset($data['error'])) {
            file_put_contents(self::CACHE_FILE, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
        }
        return $data;
    }
}
