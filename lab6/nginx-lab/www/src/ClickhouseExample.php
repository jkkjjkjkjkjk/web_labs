<?php
namespace App;

use App\Helpers\ClientFactory;
use GuzzleHttp\Client;

class ClickhouseExample
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientFactory::make('http://clickhouse:8123/', [
            'auth' => ['lab6_user', 'lab6_pass'],
        ]);
    }

    public function query(string $sql): string
    {
        $response = $this->client->post('', ['body' => $sql]);
        return $response->getBody()->getContents();
    }

    public function select(string $sql): array
    {
        $json = $this->query($sql . ' FORMAT JSON');
        $data = json_decode($json, true);
        return $data['data'] ?? [];
    }

    public function insert(string $table, array $row): void
    {
        $this->client->post('', [
            'query' => ['query' => "INSERT INTO $table FORMAT JSONEachRow"],
            'body'  => json_encode($row, JSON_UNESCAPED_UNICODE),
        ]);
    }
}