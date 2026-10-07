<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private function realClient(): Client
    {
        return new Client([
            'base_uri'    => $_ENV['APP_URL'] ?? 'http://web',
            'http_errors' => false,
            'timeout'     => 5,
        ]);
    }


    public function testIndexPageReturns200(): void
    {
        $response = $this->realClient()->get('/index.php');
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testFormPageContainsTitle(): void
    {
        $response = $this->realClient()->get('/form.html');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Заявка на ремонт техники', (string)$response->getBody());
    }


    public function testMockRequest(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'OK'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $response = $client->get('/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('OK', (string)$response->getBody());
    }

    public function testMockNotFoundThrowsException(): void
    {
        $mock = new MockHandler([
            new Response(404, [], 'Not found'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->expectException(ClientException::class);
        $client->get('/missing');
    }
}