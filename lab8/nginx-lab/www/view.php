<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Все данные</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 0 16px; }
    </style>
</head>
<body>
    <h2>Все сохранённые заявки:</h2>
    <ul>
        <?php
        $file = __DIR__ . "/data.txt";
        if (file_exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $parts = explode(";", $line);
                if (count($parts) < 5) continue;
                [$name, $model, $service, $warranty, $term] = $parts;
                echo "<li>" . htmlspecialchars($name) . " — " . htmlspecialchars($model)
                    . ", " . htmlspecialchars($service)
                    . ", гарантия: " . htmlspecialchars($warranty)
                    . ", срок: " . htmlspecialchars($term) . "</li>";
            }
        } else {
            echo "<li>Данных нет</li>";
        }
        ?>
    </ul>
    <a href="index.php">На главную</a>
</body>
</html>
