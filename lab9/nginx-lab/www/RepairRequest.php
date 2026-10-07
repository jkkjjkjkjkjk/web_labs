<?php
class RepairRequest {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function add($username, $model, $service, $warranty, $term) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO repair_requests (username, model, service, warranty, term) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$username, $model, $service, $warranty, $term]);
    }

    public function getAll(bool $warrantyOnly = false): array {
        $sql = "SELECT * FROM repair_requests";
        if ($warrantyOnly) {
            $sql .= " WHERE warranty = 1";
        }
        $sql .= " ORDER BY created_at DESC, id DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    public function getStats(): array {
        return $this->pdo->query(
            "SELECT COUNT(*) AS total, COALESCE(SUM(warranty), 0) AS with_warranty FROM repair_requests"
        )->fetch();
    }

    public function update($id, $username) {
        $stmt = $this->pdo->prepare("UPDATE repair_requests SET username = ? WHERE id = ?");
        $stmt->execute([$username, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM repair_requests WHERE id = ?");
        $stmt->execute([$id]);
    }
}