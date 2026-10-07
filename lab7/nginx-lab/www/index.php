<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/db.php';
require __DIR__ . '/RepairRequest.php';
require __DIR__ . '/QueueManager.php';
require __DIR__ . '/KafkaManager.php';

function safeCount(callable $fn): string
{
    try {
        return (string)$fn();
    } catch (\Throwable $e) {
        return 'н/д';
    }
}

$repair = new RepairRequest($pdo);
$all = $repair->getAll();

$stats = [
    ['RabbitMQ', QueueManager::MAIN_QUEUE,  'основная очередь', safeCount(fn() => (new QueueManager(QueueManager::MAIN_QUEUE))->count()),  'ждут обработки'],
    ['RabbitMQ', QueueManager::ERROR_QUEUE, 'очередь ошибок',   safeCount(fn() => (new QueueManager(QueueManager::ERROR_QUEUE))->count()), 'ждут разбора'],
    ['Kafka',    KafkaManager::MAIN_TOPIC,  'основной топик',   safeCount(fn() => (new KafkaManager(KafkaManager::MAIN_TOPIC))->count()),  'всего записано'],
    ['Kafka',    KafkaManager::ERROR_TOPIC, 'топик ошибок',     safeCount(fn() => (new KafkaManager(KafkaManager::ERROR_TOPIC))->count()), 'всего записано'],
];

$messages = [
    'sent'        => ['ok',   '✅ Заявка отправлена в очередь, worker обработает её асинхронно'],
    'bad'         => ['ok',   '🧪 Тестовое некорректное сообщение отправлено, оно должно попасть в очередь ошибок'],
    'kafka_fail'  => ['warn', '⚠️ В RabbitMQ отправлено, но Kafka недоступна'],
    'rabbit_fail' => ['err',  '❌ Не удалось отправить сообщение в RabbitMQ'],
];
$status = $_GET['status'] ?? '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5;url=index.php">
    <title>Очереди сообщений</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 950px; margin: 40px auto; padding: 0 16px; }
        .box { padding: 12px 16px; background: #f5f8fc; border-left: 4px solid #3498db; margin: 16px 0; }
        .ok { background: #eafaf1; border-color: #27ae60; }
        .warn { background: #fff8e1; border-color: #f1c40f; }
        .err { background: #fdecea; border-color: #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 8px; border: 1px solid #c5d0de; text-align: left; }
        th { background: #e8eef6; }
    </style>
</head>
<body>
    <h1>Заявки на ремонт техники (очереди)</h1>

    <?php if (isset($messages[$status])): ?>
        <div class="box <?= $messages[$status][0] ?>"><?= htmlspecialchars($messages[$status][1]) ?></div>
    <?php endif; ?>

    <p>
        <a href="form.html">Создать заявку</a>
        <form method="POST" action="send.php" style="display:inline; margin-left:16px;">
            <input type="hidden" name="bad" value="1">
            <button type="submit">Отправить тестовую ошибку</button>
        </form>
    </p>

    <h2>Статистика очередей</h2>
    <table>
        <tr><th>Система</th><th>Очередь / топик</th><th>Назначение</th><th>Сообщений</th><th>Что означает</th></tr>
        <?php foreach ($stats as [$system, $name, $purpose, $count, $meaning]): ?>
            <tr>
                <td><?= htmlspecialchars($system) ?></td>
                <td><?= htmlspecialchars($name) ?></td>
                <td><?= htmlspecialchars($purpose) ?></td>
                <td><b><?= htmlspecialchars($count) ?></b></td>
                <td><?= htmlspecialchars($meaning) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <p><small>Страница обновляется каждые 5 секунд. В RabbitMQ сообщение исчезает из очереди после обработки, а Kafka хранит записи, поэтому для неё показано общее число.</small></p>

    <h2>Обработанные заявки (из MySQL)</h2>
    <?php if (empty($all)): ?>
        <p>Пока ничего не обработано.</p>
    <?php else: ?>
        <table>
            <tr><th>ID</th><th>Имя</th><th>Модель</th><th>Услуга</th><th>Гарантия</th><th>Срок ремонта</th><th>Дата</th></tr>
            <?php foreach ($all as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= htmlspecialchars($row['model']) ?></td>
                    <td><?= htmlspecialchars($row['service']) ?></td>
                    <td><?= $row['warranty'] ? 'Да' : 'Нет' ?></td>
                    <td><?= htmlspecialchars($row['term']) ?></td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>