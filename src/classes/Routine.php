<?php

class Routine extends Model {
    private $id, $name, $address, $latitude, $longitude;
    private $pdo;

    public function __construct($id, $name = '', $address = '', $latitude = 0, $longitude = 0) {
        $this->id = $id;
        $this->name = $name;
        $this->address = $address;
        $this->latitude = $latitude;
        $this->longitude = $longitude;

        
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
    

    }

    public function update() {
        // Implement the logic to update a routine in the database
    }

    public function delete() {
        // Implement the logic to delete a routine from the database
    }
}