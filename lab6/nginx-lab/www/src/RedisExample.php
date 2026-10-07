<?php
namespace App;

use Predis\Client;

class RedisExample
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client('tcp://redis:6379');
    }

    public function setValue(string $key, string $value, ?int $ttl = null): void
    {
        if ($ttl) {
            $this->client->setex($key, $ttl, $value);
        } else {
            $this->client->set($key, $value);
        }
    }

    public function getValue(string $key): ?string
    {
        return $this->client->get($key);
    }

    public function deleteValue(string $key): void
    {
        $this->client->del([$key]);
    }
}