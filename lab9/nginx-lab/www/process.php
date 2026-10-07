<?php
session_start();
require 'db.php';
require 'RepairRequest.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: form.html");
    exit();
}

$services = ["Диагностика", "Замена экрана", "Замена аккумулятора", "Чистка от пыли", "Ремонт материнской платы"];
$terms = ["Срочный (1 день)", "Обычный (3-5 дней)", "Без спешки (до 2 недель)"];

$username = trim($_POST['username'] ?? '');
$model    = trim($_POST['model'] ?? '');
$service  = $_POST['service'] ?? '';
$warranty = isset($_POST['warranty']) ? 1 : 0;
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

$repair = new RepairRequest($pdo);
$repair->add($username, $model, $service, $warranty, $term);

header("Location: index.php");
exit();