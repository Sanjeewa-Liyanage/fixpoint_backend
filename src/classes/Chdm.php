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
    $sql = "INSERT INTO chdm (serial_no, state, location, description, tested_date, branch_id)
            VALUES (:serial_no, :state, :location, :description, :tested_date, :branch_id)";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":serial_no", $this->serial_no);
    $stmt->bindParam(":state", $this->state);
    $stmt->bindParam(":location", $this->location);
    $stmt->bindParam(":description", $this->description);
    $stmt->bindParam(":tested_date", $this->tested_date);
    $stmt->bindParam(":branch_id", $this->branch_id);

    $success = $stmt -> execute();
    return $success;
}

    public function delete() {
        // Implement delete logic here
        // Example: Delete chdm from database by $this->id
        return true;
    }

    public function read() {
        // Implement read logic here
        // Example: Fetch chdm from database by $this->id
        return true;
    }

    public function update() {
        // Implement update logic here
        // Example: Update chdm in database by $this->id
        return true;
    }
}