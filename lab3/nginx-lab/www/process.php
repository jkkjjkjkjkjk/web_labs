<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: form.html");
    exit();
}

$services = ["Диагностика", "Замена экрана", "Замена аккумулятора", "Чистка от пыли", "Ремонт материнской платы"];
$terms = ["Срочный (1 день)", "Обычный (3-5 дней)", "Без спешки (до 2 недель)"];

$username = trim($_POST['username'] ?? '');
$model    = trim($_POST['model'] ?? '');
$service  = $_POST['service'] ?? '';
$warranty = isset($_POST['warranty']) ? "Да" : "Нет";
$term     = $_POST['term'] ?? '';

$errors = [];
if ($username === '') $errors[] = "Имя не может быть пустым";
if ($model === '')    $errors[] = "Укажите модель устройства";
if (!in_array($service, $services, true)) $errors[] = "Выберите услугу из списка";
if (!in_array($term, $terms, true))       $errors[] = "Выберите срок ремонта";

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    header("Location: index.php");
    exit();
}

$clean = fn($s) => str_replace([";", "\r", "\n"], " ", $s);
$username = $clean($username);
$model    = $clean($model);

$_SESSION['username'] = $username;
$_SESSION['model']    = $model;
$_SESSION['service']  = $service;
$_SESSION['warranty'] = $warranty;
$_SESSION['term']     = $term;

$line = implode(";", [$username, $model, $service, $warranty, $term]) . "\n";
file_put_contents(__DIR__ . "/data.txt", $line, FILE_APPEND | LOCK_EX);

$expires = time() + 60 * 60 * 24 * 30;
setcookie("last_username", $username, $expires, "/");
setcookie("last_model", $model, $expires, "/");

header("Location: index.php");
exit();