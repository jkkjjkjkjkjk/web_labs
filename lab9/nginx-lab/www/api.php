<?php
session_start();
require_once __DIR__ . '/ApiClient.php';

header('Content-Type: application/json; charset=utf-8');

$api = new ApiClient();
$data = $api->getProducts(true);

$_SESSION['api_data'] = $data;

if (isset($data['error'])) {
    http_response_code(502);
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
