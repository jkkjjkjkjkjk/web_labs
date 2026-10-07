<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/QueueManager.php';
require __DIR__ . '/KafkaManager.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: form.html');
    exit();
}

$data = [
    'username' => trim($_POST['username'] ?? ''),
    'model'    => trim($_POST['model'] ?? ''),
    'service'  => $_POST['service'] ?? '',
    'warranty' => isset($_POST['warranty']) ? 1 : 0,
    'term'     => $_POST['term'] ?? '',
    'sent_at'  => date('Y-m-d H:i:s'),
];

$isBad = isset($_POST['bad']);
if ($isBad) {
    $data = [
        'username' => 'Тест ошибки',
        'model'    => '',
        'service'  => 'Диагностика',
        'warranty' => 0,
        'term'     => 'Обычный (3-5 дней)',
        'sent_at'  => date('Y-m-d H:i:s'),
    ];
}

$status = $isBad ? 'bad' : 'sent';

try {
    (new QueueManager(QueueManager::MAIN_QUEUE))->publish($data);
} catch (\Throwable $e) {
    header('Location: index.php?status=rabbit_fail');
    exit();
}

try {
    (new KafkaManager(KafkaManager::MAIN_TOPIC))->publish($data);
} catch (\Throwable $e) {
    $status = 'kafka_fail';
}

header('Location: index.php?status=' . $status);
exit();