<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../www/RepairRequest.php';

class RepairRequestMockTest extends TestCase
{
    private PDO $pdoMock;
    private RepairRequest $repair;

    protected function setUp(): void
    {
        $this->pdoMock = $this->createMock(PDO::class);
        $this->repair  = new RepairRequest($this->pdoMock);
    }

    public function testAddInsertsRow(): void
    {
        $stmt = $this->createMock(PDOStatement::class);
        $stmt->expects($this->once())
            ->method('execute')
            ->with(['Иван', 'Lenovo IdeaPad 3', 'Диагностика', 1, 'Обычный (3-5 дней)']);

        $this->pdoMock->expects($this->once())
            ->method('prepare')
            ->willReturnCallback(function (string $sql) use ($stmt) {
                $this->assertStringContainsString('INSERT INTO repair_requests', $sql);
                return $stmt;
            });

        $this->repair->add('Иван', 'Lenovo IdeaPad 3', 'Диагностика', 1, 'Обычный (3-5 дней)');
    }

    public function testGetAllReturnsRows(): void
    {
        $rows = [
            ['id' => 2, 'username' => 'Мария'],
            ['id' => 1, 'username' => 'Иван'],
        ];

        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn($rows);

        $this->pdoMock->expects($this->once())
            ->method('query')
            ->willReturn($stmt);

        $result = $this->repair->getAll();

        $this->assertCount(2, $result);
        $this->assertSame('Мария', $result[0]['username']);
    }

    // Фильтр «только с гарантией» должен попасть в SQL
    public function testGetAllWithWarrantyFilterAddsWhere(): void
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql) use ($stmt) {
                $this->assertStringContainsString('WHERE warranty = 1', $sql);
                return $stmt;
            });

        $this->repair->getAll(true);
    }

    public function testGetAllWithoutFilterHasNoWhere(): void
    {
        $stmt = $this->createStub(PDOStatement::class);
        $stmt->method('fetchAll')->willReturn([]);

        $this->pdoMock->expects($this->once())
            ->method('query')
            ->willReturnCallback(function (string $sql) use ($stmt) {
                $this->assertStringNotContainsString('WHERE', $sql);
                return $stmt;
            });

        $this->repair->getAll();
    }
}