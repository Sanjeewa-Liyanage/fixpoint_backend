<?php
class Client extends Model {
    public $client_id;
    public $name;
    public $contact_info;
    public $created_at;

    public function __construct($client_id = null, $name = null, $contact_info = null, $created_at = null) {
        $this->client_id = $client_id;
        $this->name = $name;
        $this->contact_info = $contact_info;
        $this->created_at = $created_at;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO client (name, contact_info, created_at) VALUES (:name, :contact_info, :created_at)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_info", $this->contact_info);
        $stmt->bindParam(":created_at", $this->created_at);
        return $stmt->execute();
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM client";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE client SET name = :name, contact_info = :contact_info WHERE client_id = :client_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_info", $this->contact_info);
        $stmt->bindParam(":client_id", $this->client_id);
        return $stmt->execute();
    }

    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM client WHERE client_id = :client_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":client_id", $this->client_id);
        return $stmt->execute();
    }
}
