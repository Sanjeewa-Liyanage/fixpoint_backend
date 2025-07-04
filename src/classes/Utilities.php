<?php
class Utilities extends Model{
    public $utility_id;
    public $utility_name;
    public $description;
    public $download_link;
    public $created_at;
    public $updated_at;
    public $category;
    public function __construct($utility_id = null, $utility_name = null, $description = null, $category = null, $download_link = null, $created_at = null, $updated_at = null){
        $this->utility_id = $utility_id;
        $this->utility_name = $utility_name;
        $this->description = $description;
        $this->category = $category;
        $this->download_link = $download_link;
        $this->created_at = $created_at;
        $this->updated_at = $updated_at;
    }

    public function create(){
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO utilities (utility_name, description, category, download_link, created_at) VALUES (:utility_name, :description, :category, :download_link, :created_at)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":utility_name", $this->utility_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":download_link", $this->download_link);
        $stmt->bindParam(":created_at", $this->created_at);
        


        return $stmt->execute();
    }

    public function read(){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM utilities WHERE utility_id = :utility_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":utility_id", $this->utility_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $this->utility_name = $result['utility_name'];
            $this->description = $result['description'];
            $this->download_link = $result['download_link'];
            $this->created_at = $result['created_at'];
            $this->updated_at = $result['updated_at'];
            return $result;
        }
        return false;
    }
 public static function readAll() {
        // Implementation for reading all utilities records
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM utilities ORDER BY utility_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function update(){
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE utilities SET utility_name = :utility_name, description = :description, download_link = :download_link, updated_at = :updated_at WHERE utility_id = :utility_id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":utility_id", $this->utility_id);
        $stmt->bindParam(":utility_name", $this->utility_name);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":download_link", $this->download_link);
        $stmt->bindParam(":updated_at", $this->updated_at);

        return $stmt->execute();
    }

    public function delete(){
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM utilities WHERE utility_id = :utility_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":utility_id", $this->utility_id);
        return $stmt->execute();
    }

    
}