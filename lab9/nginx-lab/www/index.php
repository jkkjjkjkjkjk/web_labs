<?php
session_start();
require 'db.php';
require 'RepairRequest.php';

$repair = new RepairRequest($pdo);
$warrantyOnly = isset($_GET['filter']) && $_GET['filter'] === 'warranty';
$all = $repair->getAll($warrantyOnly);
$stats = $repair->getStats();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Заявки на ремонт</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 0 16px; }
        .errors { color: red; }
        .box { padding: 12px 16px; background: #f5f8fc; border-left: 4px solid #3498db; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #c5d0de; text-align: left; }
        th { background: #e8eef6; }
    </style>
</head>
<body>
    <h1>Заявки на ремонт техники</h1>

    <?php if (isset($_SESSION['errors'])): ?>
        <ul class="errors">
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php unset($_SESSION['errors']); ?>
    <?php endif; ?>

    <div class="box">
        <b>Статистика:</b> всего заявок — <?= (int)$stats['total'] ?>,
        с гарантией — <?= (int)$stats['with_warranty'] ?>
    </div>

    <p>
        Фильтр:
        <a href="index.php">Все</a> |
        <a href="index.php?filter=warranty">Только с гарантией</a>
    </p>

    <h2>Сохранённые данные:</h2>
    <?php if (empty($all)): ?>
        <p>Данных пока нет.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th><th>Имя</th><th>Модель</th><th>Услуга</th>
                <th>Гарантия</th><th>Срок ремонта</th><th>Дата</th>
            </tr>
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

    <p><a href="form.html">Заполнить форму</a></p>
</body>
</html>