<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../www/RepairRequest.php';

class RepairRequestIntegrationTest extends TestCase
{
    private PDO $pdo;
    private RepairRequest $repair;

    protected function setUp(): void
    {
        $dbName = $_ENV['DB_NAME'] ?? '';

        if ($dbName !== 'test_db') {
            $this->markTestSkipped('DB_NAME должен быть test_db (проверь .env.test)');
        }

        try {
            $this->pdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'] ?? 'db', $dbName),
                $_ENV['DB_USER'] ?? '',
                $_ENV['DB_PASSWORD'] ?? '',
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $e) {
            $this->markTestSkipped('Тестовая БД недоступна: ' . $e->getMessage());
        }

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS repair_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                model VARCHAR(100) NOT NULL,
                service VARCHAR(100) NOT NULL,
                warranty TINYINT(1) NOT NULL DEFAULT 0,
                term VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) CHARACTER SET utf8mb4
        ");

        $this->pdo->exec('TRUNCATE TABLE repair_requests');

        $this->repair = new RepairRequest($this->pdo);
    }

    private function countRows(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM repair_requests')->fetchColumn();
    }

    public function testUsesSeparateTestDatabase(): void
    {
        $this->assertSame('test_db', $this->pdo->query('SELECT DATABASE()')->fetchColumn());
    }

    public function testAddAndGetAll(): void
    {
        $this->repair->add('Иван', 'Lenovo', 'Диагностика', 1, 'Обычный (3-5 дней)');
        $this->repair->add('Мария', 'Asus', 'Замена экрана', 0, 'Срочный (1 день)');

        $all = $this->repair->getAll();

        $this->assertCount(2, $all);
        $this->assertSame('Мария', $all[0]['username']);
        $this->assertSame('Иван', $all[1]['username']);
    }

    public function testRecordCount(): void
    {
        $this->repair->add('Иван', 'Lenovo', 'Диагностика', 1, 'Обычный (3-5 дней)');
        $this->repair->add('Мария', 'Asus', 'Замена экрана', 0, 'Срочный (1 день)');
        $this->repair->add('Пётр', 'Dell', 'Чистка от пыли', 1, 'Без спешки (до 2 недель)');

        $this->assertSame(3, $this->countRows());

        $stats = $this->repair->getStats();
        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['with_warranty']);
    }

    public function testWarrantyFilter(): void
    {
        $this->repair->add('Иван', 'Lenovo', 'Диагностика', 1, 'Обычный (3-5 дней)');
        $this->repair->add('Мария', 'Asus', 'Замена экрана', 0, 'Срочный (1 день)');
        $this->repair->add('Пётр', 'Dell', 'Чистка от пыли', 1, 'Без спешки (до 2 недель)');

        $filtered = $this->repair->getAll(true);

        $this->assertCount(2, $filtered);
        foreach ($filtered as $row) {
            $this->assertEquals(1, $row['warranty']);
        }
    }

    public function testUpdateChangesName(): void
    {
        $this->repair->add('Иван', 'Lenovo', 'Диагностика', 1, 'Обычный (3-5 дней)');
        $id = $this->repair->getAll()[0]['id'];

        $this->repair->update($id, 'Иван Петров');

        $this->assertSame('Иван Петров', $this->repair->getAll()[0]['username']);
    }

    public function testDeleteRemovesRecord(): void
    {
        $this->repair->add('Иван', 'Lenovo', 'Диагностика', 1, 'Обычный (3-5 дней)');
        $id = $this->repair->getAll()[0]['id'];

        $this->repair->delete($id);

        $this->assertSame(0, $this->countRows());
    }

    public function testInvalidDataIsRejected(): void
    {
        try {
            $this->repair->add(null, 'Lenovo', 'Диагностика', 0, 'Обычный (3-5 дней)');
            $this->fail('Ожидалось исключение PDOException');
        } catch (PDOException $e) {
            $this->assertStringContainsString('username', $e->getMessage());
        }

        $this->assertSame(0, $this->countRows());
    }
}