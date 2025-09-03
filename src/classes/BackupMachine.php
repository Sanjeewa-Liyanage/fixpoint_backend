<?php

class BackupMachine extends Model {
    public $backup_id;
    public $serial_no;
    public $model;
    public $status;

    public function __construct($backup_id = null, $serial_no = null, $model = null, $status = null) {
        $this->backup_id = $backup_id;
        $this->serial_no = $serial_no;
        $this->model = $model;
        $this->status = $status;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO backup_machine (serial_no, model, status) VALUES (:serial_no, :model, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':serial_no', $this->serial_no);
        $stmt->bindParam(':model', $this->model);
        $stmt->bindParam(':status', $this->status);
        $success = $stmt->execute();
        
        if ($success) {
            $this->backup_id = $conn->lastInsertId();
        }
        
        return $success;
    }
// (status IN ('available', 'in_use', 'maintenance', 'retired'))
    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM backup_machine WHERE backup_id = :backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $this->backup_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return false; // Record not found
        }
        
        $this->serial_no = $result['serial_no'];
        $this->model = $result['model'];
        $this->status = $result['status'];
        return true;
    }

    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE backup_machine SET serial_no = :serial_no, model = :model, status = :status WHERE backup_id = :backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $this->backup_id);
        $stmt->bindParam(':serial_no', $this->serial_no);
        $stmt->bindParam(':model', $this->model);
        $stmt->bindParam(':status', $this->status);
        
        return $stmt->execute();
    }

    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM backup_machine WHERE backup_id = :backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $this->backup_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public static function getAll() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM backup_machine ORDER BY backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByStatus($status) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM backup_machine WHERE status = :status ORDER BY backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function searchBySerialNo($serial_no) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM backup_machine WHERE serial_no LIKE :serial_no ORDER BY backup_id";
        $stmt = $conn->prepare($sql);
        $likeSerial = '%' . $serial_no . '%';
        $stmt->bindParam(':serial_no', $likeSerial);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
