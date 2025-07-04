<?php
class Stock extends Model {
    public $stock_id;
    public $item_id;
    public $quantity;
    public $location;
    public $min_threshold;
    public $last_updated;

    public function __construct($stock_id = null, $item_id = null, $quantity = null, $location = null,$min_threshold=null, $last_updated = null) {
        $this->stock_id = $stock_id;
        $this->item_id = $item_id;
        $this->quantity = $quantity;
        $this->location = $location;
        $this->min_threshold = $min_threshold;
        $this->last_updated = $last_updated;
    }

    public function create() {
        // Implementation for creating a stock record
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO stock (item_id, quantity, location, min_threshold, last_updated)
                VALUES (:item_id, :quantity, :location, :min_threshold, :last_updated)";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":item_id", $this->item_id);
                $stmt->bindParam(":quantity", $this->quantity);
                $stmt->bindParam(":location", $this->location);
                $stmt->bindParam(":min_threshold", $this->min_threshold);
                $stmt->bindParam(":last_updated", $this->last_updated);
                return $stmt->execute();

    }

    public function read() {
        // Implementation for reading a stock record
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT item_id, quantity, location, min_threshold, last_updated FROM stock WHERE stock_id = :stock_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":stock_id", $this->stock_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->item_id = $result['item_id'];
            $this->quantity = $result['quantity'];
            $this->location = $result['location'];
            $this->min_threshold = $result['min_threshold'];
            $this->last_updated = $result['last_updated'];


            return $result;
        }
        return false;
    }
     public static function readAll() {
        // Implementation for reading all stock records
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM stock ORDER BY stock_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update() {
        // Implementation for updating a stock record
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE stock SET item_id = :item_id, quantity = :quantity, location = :location, min_threshold = :min_threshold, last_updated = :last_updated WHERE stock_id = :stock_id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":stock_id", $this->stock_id);
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":min_threshold", $this->min_threshold);
        $stmt->bindParam(":last_updated", $this->last_updated);
        return $stmt->execute();
    }

    public function delete() {
        // Implementation for deleting a stock record
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM stock WHERE stock_id = :stock_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":stock_id", $this->stock_id);

        return $stmt->execute();
    }

    
}