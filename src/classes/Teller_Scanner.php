<?php
class Teller_Scanner extends Model {
    public $scanner_id;
    public $serial_number;
    public $model;
    public $status;
    public $branch_id;
    public $remarks;
    public $manufactured_date;
    public $warranty_expiry;

    public function __construct($scanner_id = null, $serial_number = null, $model = null, $status = null, $branch_id = null, $remarks = null, $manufactured_date = null, $warranty_expiry = null) {
        $this->scanner_id = $scanner_id;
        $this->serial_number = $serial_number;
        $this->model = $model;
        $this->status = $status;
        $this->branch_id = $branch_id;
        $this->remarks = $remarks;
        $this->manufactured_date = $manufactured_date;
        $this->warranty_expiry = $warranty_expiry;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO teller_scanner (serial_number, model, status, branch_id, remarks, manufactured_date, warranty_expiry)
                VALUES (:serial_number, :model, :status, :branch_id, :remarks, :manufactured_date, :warranty_expiry)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":serial_number", $this->serial_number);
        $stmt->bindParam(":model", $this->model);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":branch_id", $this->branch_id);
        $stmt->bindParam(":remarks", $this->remarks);
        $stmt->bindParam(":manufactured_date", $this->manufactured_date);
        $stmt->bindParam(":warranty_expiry", $this->warranty_expiry);

        return $stmt->execute();
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT serial_number, model, status, branch_id, remarks, manufactured_date, warranty_expiry FROM teller_scanner WHERE scanner_id = :scanner_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":scanner_id", $this->scanner_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $this->serial_number = $result['serial_number'];
            $this->model = $result['model'];
            $this->status = $result['status'];
            $this->branch_id = $result['branch_id'];
            $this->remarks = $result['remarks'];
            $this->manufactured_date = $result['manufactured_date'];
            $this->warranty_expiry = $result['warranty_expiry'];
            return $result;
        }
        return false;
    }

     public static function readAll() {
        // Implementation for reading all teller scanner records
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM teller_scanner ORDER BY scanner_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE teller_scanner SET serial_number = :serial_number, model = :model, status = :status, branch_id = :branch_id, remarks = :remarks, manufactured_date = :manufactured_date, warranty_expiry = :warranty_expiry WHERE scanner_id = :scanner_id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":scanner_id", $this->scanner_id);
        $stmt->bindParam(":serial_number", $this->serial_number);
        $stmt->bindParam(":model", $this->model);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":branch_id", $this->branch_id);
        $stmt->bindParam(":remarks", $this->remarks);
        $stmt->bindParam(":manufactured_date", $this->manufactured_date);
        $stmt->bindParam(":warranty_expiry", $this->warranty_expiry);

        return $stmt->execute();
    }

    public function update_all($data) {
        $conn = DatabaseConnection::getConnection();
        $results = [];
        foreach ($data as $scannerData) {
            $this->scanner_id = $scannerData['scanner_id'];
            $this->serial_number = $scannerData['serial_number'];
            $this->model = $scannerData['model'];
            $this->status = $scannerData['status'];
            $this->branch_id = $scannerData['branch_id'];
            $this->remarks = $scannerData['remarks'];
            $this->manufactured_date = $scannerData['manufactured_date'];
            $this->warranty_expiry = $scannerData['warranty_expiry'];

            if ($this->update()) {
                $results[] = ['scanner_id' => $this->scanner_id, 'status' => 'success'];
            } else {
                $results[] = ['scanner_id' => $this->scanner_id, 'status' => 'error'];
            }
        }
        return $results;
    }
    
    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM teller_scanner WHERE scanner_id = :scanner_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":scanner_id", $this->scanner_id);
        return $stmt->execute();
    }

    // Static function to update any fields for a given scanner_id
    public static function updateAll($fields) {
        if (!isset($fields['scanner_id'])) {
            return false; // scanner_id is required
        }
        $scanner_id = $fields['scanner_id'];
        unset($fields['scanner_id']);

        if (empty($fields)) {
            return false; // nothing to update
        }

        $setClauses = [];
        $params = [];
        foreach ($fields as $key => $value) {
            $setClauses[] = "$key = :$key";
            $params[":$key"] = $value;
        }
        $setClause = implode(', ', $setClauses);

        $sql = "UPDATE teller_scanner SET $setClause WHERE scanner_id = :scanner_id";
        $conn = DatabaseConnection::getConnection();
        $stmt = $conn->prepare($sql);

        foreach ($params as $param => $value) {
            $stmt->bindValue($param, $value);
        }
        $stmt->bindValue(":scanner_id", $scanner_id);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

   

}