<?php session_start(); ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 0 16px; }
        .errors { color: red; }
        .box { padding: 12px 16px; background: #f5f8fc; border-left: 4px solid #3498db; margin: 16px 0; }
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

    <?php if (isset($_SESSION['username'])): ?>
        <div class="box">
            <p><b>Данные из сессии:</b></p>
            <ul>
                <li>Имя: <?= htmlspecialchars($_SESSION['username']) ?></li>
                <li>Модель: <?= htmlspecialchars($_SESSION['model']) ?></li>
                <li>Услуга: <?= htmlspecialchars($_SESSION['service']) ?></li>
                <li>Гарантия: <?= htmlspecialchars($_SESSION['warranty']) ?></li>
                <li>Срок ремонта: <?= htmlspecialchars($_SESSION['term']) ?></li>
            </ul>
        </div>
    <?php else: ?>
        <p>Данных пока нет.</p>
    <?php endif; ?>

    <?php if (isset($_COOKIE['last_username'])): ?>
        <div class="box">
            <p><b>Данные из куки:</b></p>
            <ul>
                <li>Последнее имя: <?= htmlspecialchars($_COOKIE['last_username']) ?></li>
                <li>Последняя модель: <?= htmlspecialchars($_COOKIE['last_model'] ?? '') ?></li>
            </ul>
        </div>
    <?php endif; ?>

    <a href="form.html">Заполнить форму</a> |
    <a href="view.php">Посмотреть все данные</a>
</body>
</html>
