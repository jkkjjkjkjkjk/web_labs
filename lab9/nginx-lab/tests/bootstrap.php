<?php

require __DIR__ . '/../vendor/autoload.php';

// Переменные окружения для тестов
Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.test')->safeLoad();