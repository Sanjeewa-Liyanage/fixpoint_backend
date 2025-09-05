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
    
    public function readAll() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT chdm.*, branch.branch_id AS branch_branch_id, branch.client_id AS branch_client_id, branch.name AS branch_name, branch.address AS branch_address, branch.latitude AS branch_latitude, branch.longitude AS branch_longitude, branch.location AS branch_location, branch.contact_person AS branch_contact_person, branch.phone AS branch_phone, branch.email AS branch_email FROM chdm LEFT JOIN branch ON chdm.branch_id = branch.branch_id ORDER BY chdm.tested_date DESC";
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
    static public function getNotAssigned() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM chdm WHERE branch_id IS NULL AND state = 'passed'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    static function assignForBranch($serial_no, $branch_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE chdm SET branch_id = :branch_id, location = :location WHERE serial_no = :serial_no";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->bindParam(':location', $branch_id); // Set location to branch_id
        $stmt->bindParam(':serial_no', $serial_no);
        $success = $stmt->execute();
        return $success;
    }
    static public function searchFailedChdm($keyword) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM chdm WHERE state = 'failed' AND (serial_no LIKE :keyword)";
        $stmt = $conn->prepare($sql);
        $searchKeyword = '%' . $keyword . '%';
        $stmt->bindParam(':keyword', $searchKeyword);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$results) {
            return false; // No results found
        }
        return $results;
        
    }
    static function searchWithBranchAndState(){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM chdm WHERE state = 'passed' AND branch_id IS NOT NULL";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!$results) {
            return false; // No results found
        }
        return $results;
    }

    public function update_all() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE chdm SET serial_no = :serial_no, state = :state, location = :location, description = :description, tested_date = :tested_date WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':serial_no', $this->serial_no);
        $stmt->bindParam(':state', $this->state);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':tested_date', $this->tested_date);
        $stmt->bindParam(':id', $this->id);
        $success = $stmt->execute();
        return $success;
    }
}
