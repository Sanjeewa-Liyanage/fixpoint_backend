<?php
class Chdm extends Model{
    public $id;
    public $serial_no;
    public $state;
    public $location;
    public $description;
    public $tested_date;
    public $branch_id;


    public function __construct($id = null, $serial_no = null, $state = null, $location = null, $description = null, $tested_date = null, $branch_id = null){
        $this->id = $id;
        $this->serial_no = $serial_no;
        $this->state = $state;
        $this->location = $location;
        $this->description = $description;
        $this->tested_date = $tested_date;
        $this->branch_id = $branch_id;
    }

    public function create(){
    $conn = DatabaseConnection::getConnection();
    $sql = "INSERT INTO chdm (serial_no, state, location, description, tested_date)
            VALUES (:serial_no, :state, :location, :description, :tested_date )";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":serial_no", $this->serial_no);
    $stmt->bindParam(":state", $this->state);
    $stmt->bindParam(":location", $this->location);
    $stmt->bindParam(":description", $this->description);
    $stmt->bindParam(":tested_date", $this->tested_date);
   

    $success = $stmt -> execute();
    return $success;
}

    // Removed duplicate delete() method to avoid redeclaration error.

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM chdm WHERE state = 'passed'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
        
        
    }
    public function read_failed() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM chdm WHERE state = 'failed'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
        
    }
     public function update_status($status) {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE chdm SET state = :state WHERE serial_no = :serial_no";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':state', $status);
        $stmt->bindParam(':serial_no', $this->serial_no);
        $success = $stmt->execute();
        return $success;
    }
    public function Update_location($location) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE chdm SET location = :location WHERE serial_no = :serial_no';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam('location', $location);
        $stmt->bindParam('serial_no', $this->serial_no);
        $success = $stmt->execute();
        return $success;
    }
    
    public function Update_branch_id($branch_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE chdm SET branch_id = :branch_id WHERE serial_no = :serial_no';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam('branch_id', $branch_id);
        $stmt->bindParam('serial_no', $this->serial_no);
        $success = $stmt->execute();
        return $success;
    }

    public function update() {
        // Implement update logic here
        // Example: Update chdm in database by $this->id
        return true;
    }
    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM chdm WHERE serial_no = :serial_no";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":serial_no", $this->serial_no);
        $success = $stmt->execute();
        return $success;
    }

    
}
