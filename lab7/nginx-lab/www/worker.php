<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';
require __DIR__ . '/RepairRequest.php';
require __DIR__ . '/QueueManager.php';
require __DIR__ . '/RequestValidator.php';

$repair = new RepairRequest($pdo);
$main   = new QueueManager(QueueManager::MAIN_QUEUE);
$errors = new QueueManager(QueueManager::ERROR_QUEUE);

echo "Рабочий запущен (RabbitMQ)...\n";

$main->consume(function (array $data) use ($repair, $errors) {
    echo "Получено сообщение: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

    try {
        RequestValidator::validate($data);
        sleep(2);

        $repair->add($data['username'], $data['model'], $data['service'], (int)$data['warranty'], $data['term']);
        file_put_contents(__DIR__ . '/processed_rabbit.log', json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);

        echo "Обработано\n";
    } catch (\Throwable $e) {
        $errors->publish([
            'data'      => $data,
            'error'     => $e->getMessage(),
            'failed_at' => date('Y-m-d H:i:s'),
        ]);
        echo "Ошибка: " . $e->getMessage() . " сообщение отправлено в " . QueueManager::ERROR_QUEUE . "\n";
    }
});