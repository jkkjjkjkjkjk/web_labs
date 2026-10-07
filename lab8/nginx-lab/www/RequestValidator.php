<?php

class RequestValidator
{
    public const SERVICES = ["Диагностика", "Замена экрана", "Замена аккумулятора", "Чистка от пыли", "Ремонт материнской платы"];
    public const TERMS = ["Срочный (1 день)", "Обычный (3-5 дней)", "Без спешки (до 2 недель)"];

    public static function validate(array $d): void
    {
        if (trim((string)($d['username'] ?? '')) === '') {
            throw new InvalidArgumentException('Пустое имя');
        }
        if (trim((string)($d['model'] ?? '')) === '') {
            throw new InvalidArgumentException('Пустая модель устройства');
        }
        if (!in_array($d['service'] ?? '', self::SERVICES, true)) {
            throw new InvalidArgumentException('Неизвестная услуга');
        }
        if (!in_array($d['term'] ?? '', self::TERMS, true)) {
            throw new InvalidArgumentException('Неизвестный срок ремонта');
        }
    }
}