 
<?php
class dispatch extends Model {
    public $dispatch_id;
    public $item_id;
    public $qc_officer_id;
    public $warehouse_name;
    public $quantity;
    public $date;
    public $purpose;
    public $status;

    public function __construct($dispatch_id = null, $item_id = null, $qc_officer_id = null, $warehouse_name = null, $quantity = null, $date = null, $purpose = null, $status = null) {
        $this->dispatch_id = $dispatch_id;
        $this->item_id = $item_id;
        $this->qc_officer_id = $qc_officer_id;
        $this->warehouse_name = $warehouse_name;
        $this->quantity = $quantity;
        $this->date = $date;
        $this->purpose = $purpose;
        $this->status = $status;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO dispatch (item_id, qc_officer_id, warehouse_name, quantity, date, purpose, status) 
                VALUES (:item_id, :qc_officer_id, :warehouse_name, :quantity, :date, :purpose, :status)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->bindParam(":qc_officer_id", $this->qc_officer_id);
        $stmt->bindParam(":warehouse_name", $this->warehouse_name);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":purpose", $this->purpose);
        $stmt->bindParam(":status", $this->status);
        return $stmt->execute();
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM dispatch WHERE dispatch_id = :dispatch_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":dispatch_id", $this->dispatch_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    static public function getItemNameById($item_id) {
        require_once __DIR__ . '/Inventory_Item.php';
        $item = new Inventory_Item($item_id);
        $result = $item->read();
        return $result ? $result['item_name'] : null;
    }

        static public function readAll() {
        $conn = DatabaseConnection::getConnection();
        
        // The updated SQL query
        $sql = "SELECT 
                    d.dispatch_id, 
                    d.item_id, 
                    d.warehouse_name, 
                    d.quantity, 
                    d.date, 
                    d.purpose, 
                    d.status, 
                    i.item_name, 
                    COALESCE(u.username, 'Not Assigned') AS qc_officer_name
                FROM 
                    dispatch d
                LEFT JOIN 
                    inventory_item i ON d.item_id = i.item_id
                LEFT JOIN 
                    users u ON d.qc_officer_id = u.user_id";
                    
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $dispatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $dispatches;
    }

    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE dispatch SET item_id = :item_id, qc_officer_id = :qc_officer_id, warehouse_name = :warehouse_name, quantity = :quantity, date = :date, purpose = :purpose, status = :status WHERE dispatch_id = :dispatch_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->bindParam(":qc_officer_id", $this->qc_officer_id);
        $stmt->bindParam(":warehouse_name", $this->warehouse_name);
        $stmt->bindParam(":quantity", $this->quantity);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":purpose", $this->purpose);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":dispatch_id", $this->dispatch_id);
        $success = $stmt->execute();
        return $success;
    }

    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM dispatch WHERE dispatch_id = :dispatch_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":dispatch_id", $this->dispatch_id);
        $success = $stmt->execute();
        return $success;
    }
}
