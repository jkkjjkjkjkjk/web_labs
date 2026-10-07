<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../www/RequestValidator.php';

class RequestValidatorTest extends TestCase
{
    private static function validData(array $override = []): array
    {
        return array_merge([
            'username' => 'Иван',
            'model'    => 'Lenovo IdeaPad 3',
            'service'  => 'Диагностика',
            'warranty' => 1,
            'term'     => 'Обычный (3-5 дней)',
        ], $override);
    }

    public function testValidDataPasses(): void
    {
        $this->expectNotToPerformAssertions();
        RequestValidator::validate(self::validData());
    }

    public function testEmptyUsernameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Пустое имя');

        RequestValidator::validate(self::validData(['username' => '   ']));
    }

    public static function invalidProvider(): array
    {
        return [
            'пустая модель'      => [['model' => ''], 'Пустая модель устройства'],
            'неизвестная услуга' => [['service' => 'Покраска'], 'Неизвестная услуга'],
            'неизвестный срок'   => [['term' => 'Вчера'], 'Неизвестный срок ремонта'],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function testInvalidFieldsThrow(array $override, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        RequestValidator::validate(self::validData($override));
    }
}