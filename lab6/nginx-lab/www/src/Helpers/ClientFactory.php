<?php
namespace App\Helpers;

use GuzzleHttp\Client;

class ClientFactory
{
    public static function make(string $baseUri, array $options = []): Client
    {
        return new Client(array_merge([
            'base_uri' => $baseUri,
            'timeout'  => 5.0,
        ], $options));
    }
}