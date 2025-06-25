<?php

class Routine extends Model {
    public $routine_id;
    public $planned_date;
    public $status;
    public $description;
    public $response_count;
    public $branches; // array of branch data

    public function __construct($routine_id = null, $planned_date = null, $status = 'pending', $description = null, $response_count = 0, $branches = []) {
        $this->routine_id = $routine_id;
        $this->planned_date = $planned_date;
        $this->status = $status;
        $this->description = $description;
        $this->response_count = $response_count;
        $this->branches = $branches;
    }

    public static function getNearby($lat, $lng, $radius_km = 10) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT branch_id,client_id,name, address, latitude, longitude
                FROM branch
                WHERE ST_DWithin(
                    location,
                    ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography,
                    :radius
                )";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindValue(':radius', $radius_km * 1000); // convert to meters
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create() {
        // Implement the logic to create a routine in the database
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM routines WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->routine_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->routine_id = $result['id'];
        $this->planned_date = $result['planned_date'];
        $this->status = $result['status'];
        $this->description = $result['description'];
        $this->response_count = $result['response_count'];
        $this->branches = json_decode($result['branches'], true);
        return $result['id'] !== null;
    }

    public function update() {
        //update the routine as accepted by the admin
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE routines SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->routine_id);
        $stmt->bindParam(':status', $this->status);
        return $stmt->execute();
    }

    public function delete() {
        // Implement the logic to delete a routine from the database
    }
}