<?php
session_start();
require_once __DIR__ . '/UserInfo.php';

$apiData  = $_SESSION['api_data'] ?? null;
$products = $apiData['products'] ?? [];
$info     = UserInfo::getInfo();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 0 16px; }
        .errors { color: red; }
        .box { padding: 12px 16px; background: #f5f8fc; border-left: 4px solid #3498db; margin: 16px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px; border: 1px solid #c5d0de; text-align: left; }
        th { background: #e8eef6; }
        button { padding: 8px 14px; cursor: pointer; }
        #apiStatus { margin-left: 10px; font-size: 14px; }
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
                <?php if (isset($_COOKIE['last_submission'])): ?>
                    <li>Последняя отправка: <?= htmlspecialchars($_COOKIE['last_submission']) ?></li>
                <?php endif; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="box">
        <h3>Информация о пользователе:</h3>
        <?php foreach ($info as $key => $val): ?>
            <?= htmlspecialchars($key) ?>: <?= htmlspecialchars($val) ?><br>
        <?php endforeach; ?>
    </div>

    <?php if ($apiData !== null): ?>
        <h3>Данные из API (смартфоны):</h3>
        <button type="button" id="refreshBtn">Обновить данные</button>
        <span id="apiStatus" class="<?= isset($apiData['error']) ? 'errors' : '' ?>">
            <?= isset($apiData['error']) ? htmlspecialchars($apiData['error']) : '' ?>
        </span>

        <table>
            <thead>
                <tr><th>Название</th><th>Бренд</th><th>Цена, $</th></tr>
            </thead>
            <tbody id="productsBody">
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($p['brand'] ?? '') ?></td>
                        <td><?= htmlspecialchars((string)($p['price'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p>
        <a href="form.html">Заполнить форму</a> |
        <a href="view.php">Посмотреть все данные</a>
    </p>

    <script>
        const btn = document.getElementById("refreshBtn");
        const status = document.getElementById("apiStatus");
        const tbody = document.getElementById("productsBody");

        function renderProducts(products) {
            tbody.innerHTML = "";
            products.forEach(p => {
                const tr = document.createElement("tr");
                [p.title, p.brand, p.price].forEach(value => {
                    const td = document.createElement("td");
                    td.textContent = value ?? "";
                    tr.appendChild(td);
                });
                tbody.appendChild(tr);
            });
        }

        if (btn) {
            btn.addEventListener("click", async () => {
                btn.disabled = true;
                status.className = "";
                status.textContent = "Загрузка...";

                try {
                    const res = await fetch("api.php");
                    const data = await res.json();

                    if (!res.ok || data.error) {
                        throw new Error(data.error || "ошибка сервера");
                    }

                    renderProducts(data.products || []);
                    status.textContent = "Обновлено: " + new Date().toLocaleTimeString();
                } catch (err) {
                    status.className = "errors";
                    status.textContent = "Не удалось обновить данные: " + err.message;
                } finally {
                    btn.disabled = false;
                }
            });
        }
    </script>
</body>
</html>
