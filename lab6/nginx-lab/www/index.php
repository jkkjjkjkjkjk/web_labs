<?php
require __DIR__ . '/vendor/autoload.php';

use App\ClickhouseExample;
use App\RedisExample;

const TABLE = 'lab6.analytics_events';
const STATS_KEY = 'analytics:stats';

$pages = ['/', '/catalog', '/cart', '/contacts'];
$types = ['view' => 'Просмотр', 'click' => 'Клик', 'purchase' => 'Покупка'];

$click = new ClickhouseExample();
$redis = new RedisExample();

$errors = [];
$stats = [];
$latest = [];
$source = '';
$redisDemo = '';

function getStats(ClickhouseExample $click, RedisExample $redis, string &$source): array
{
    try {
        $cached = $redis->getValue(STATS_KEY);
        if ($cached) {
            $source = 'Redis (кеш на 60 секунд)';
            return json_decode($cached, true);
        }
    } catch (\Throwable $e) {
    }

    $stats = [
        'total' => $click->select(
            "SELECT count() AS total, round(avg(duration_sec), 1) AS avg_duration FROM " . TABLE
        )[0] ?? [],
        'by_page' => $click->select(
            "SELECT page, count() AS cnt, round(avg(duration_sec), 1) AS avg_duration
             FROM " . TABLE . " GROUP BY page ORDER BY cnt DESC"
        ),
        'by_type' => $click->select(
            "SELECT event_type, count() AS cnt FROM " . TABLE . " GROUP BY event_type ORDER BY cnt DESC"
        ),
    ];

    try {
        $redis->setValue(STATS_KEY, json_encode($stats, JSON_UNESCAPED_UNICODE), 60);
    } catch (\Throwable $e) {
    }

    $source = 'ClickHouse (свежий запрос)';
    return $stats;
}

try {
    $click->query("
        CREATE TABLE IF NOT EXISTS " . TABLE . " (
            event_time DateTime,
            user_name String,
            page String,
            event_type String,
            duration_sec UInt32
        ) ENGINE = MergeTree()
        ORDER BY event_time
    ");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = trim($_POST['user_name'] ?? '');
        $page = $_POST['page'] ?? '';
        $type = $_POST['event_type'] ?? '';
        $duration = filter_var($_POST['duration'] ?? '', FILTER_VALIDATE_INT);

        if ($user === '') $errors[] = 'Имя не может быть пустым';
        if (!in_array($page, $pages, true)) $errors[] = 'Выберите страницу из списка';
        if (!isset($types[$type])) $errors[] = 'Выберите тип события';
        if ($duration === false || $duration < 0 || $duration > 86400) {
            $errors[] = 'Длительность должна быть числом от 0 до 86400';
        }

        if (!$errors) {
            $click->insert(TABLE, [
                'event_time'   => date('Y-m-d H:i:s'),
                'user_name'    => $user,
                'page'         => $page,
                'event_type'   => $type,
                'duration_sec' => $duration,
            ]);

            try { $redis->deleteValue(STATS_KEY); } catch (\Throwable $e) {}

            header('Location: index.php');
            exit();
        }
    }

    $stats = getStats($click, $redis, $source);
    $latest = $click->select(
        "SELECT event_time, user_name, page, event_type, duration_sec
         FROM " . TABLE . " ORDER BY event_time DESC LIMIT 10"
    );
} catch (\Throwable $e) {
    $errors[] = 'Ошибка работы с ClickHouse: ' . $e->getMessage();
}

try {
    $redis->setValue('framework', 'predis');
    $redisDemo = $redis->getValue('framework');
} catch (\Throwable $e) {
    $redisDemo = 'Redis недоступен';
}

$total = (int)($stats['total']['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Аналитика (ClickHouse)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .errors { color: red; }
        .box { padding: 12px 16px; background: #f5f8fc; border-left: 4px solid #3498db; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { padding: 8px; border: 1px solid #c5d0de; text-align: left; }
        th { background: #e8eef6; }
        label { display: block; margin: 8px 0; }
        input[type=text], input[type=number], select { padding: 6px; width: 260px; }
    </style>
</head>
<body>
    <h1>Аналитика событий</h1>

    <?php if ($errors): ?>
        <ul class="errors">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="box">
        <h3>Добавить событие</h3>
        <form method="POST" action="index.php">
            <label>Пользователь:
                <input type="text" name="user_name" required>
            </label>
            <label>Страница:
                <select name="page" required>
                    <?php foreach ($pages as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <div>Тип события:
                <?php foreach ($types as $value => $label): ?>
                    <label style="display:inline-block; margin-right:12px;">
                        <input type="radio" name="event_type" value="<?= $value ?>" required>
                        <?= htmlspecialchars($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <label>Длительность, сек:
                <input type="number" name="duration" min="0" max="86400" value="10" required>
            </label>
            <button type="submit">Добавить</button>
        </form>
    </div>

    <?php if ($stats): ?>
        <div class="box">
            <h3>Статистика</h3>
            <p>Всего событий: <b><?= $total ?></b>
                <?php if ($total > 0): ?>
                    , средняя длительность: <b><?= htmlspecialchars((string)$stats['total']['avg_duration']) ?></b> сек
                <?php endif; ?>
            </p>
            <p>Источник данных: <b><?= htmlspecialchars($source) ?></b></p>

            <?php if ($total > 0): ?>
                <table>
                    <tr><th>Страница</th><th>Событий</th><th>Средняя длительность, сек</th></tr>
                    <?php foreach ($stats['by_page'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['page']) ?></td>
                            <td><?= htmlspecialchars((string)$row['cnt']) ?></td>
                            <td><?= htmlspecialchars((string)$row['avg_duration']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

                <table>
                    <tr><th>Тип события</th><th>Событий</th></tr>
                    <?php foreach ($stats['by_type'] as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($types[$row['event_type']] ?? $row['event_type']) ?></td>
                            <td><?= htmlspecialchars((string)$row['cnt']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <h3>Последние события</h3>
        <?php if (!$latest): ?>
            <p>Событий пока нет.</p>
        <?php else: ?>
            <table>
                <tr><th>Время</th><th>Пользователь</th><th>Страница</th><th>Тип</th><th>Длительность, сек</th></tr>
                <?php foreach ($latest as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['event_time']) ?></td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= htmlspecialchars($row['page']) ?></td>
                        <td><?= htmlspecialchars($types[$row['event_type']] ?? $row['event_type']) ?></td>
                        <td><?= htmlspecialchars((string)$row['duration_sec']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <p>Redis (демо из методички): framework = <b><?= htmlspecialchars((string)$redisDemo) ?></b></p>
</body>
</html>