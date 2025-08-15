<?php
class Inventory_Item extends Model{
    public $item_id;
    public $item_name;
    public $category;
    public $description;
    public $manufacturer;
    public $created_at;


     // Add allowed categories as a static property
    private static $allowed_categories = ['CHDM', 'Teller Scanner'];

    // Add a static method to get allowed categories
    public static function getAllowedCategories() {
        return self::$allowed_categories;
    }

    // Optionally, add a setter with validation for category
    public function setCategory($category) {
        if (!in_array($category, self::$allowed_categories)) {
            throw new InvalidArgumentException("Invalid category");
        }
        $this->category = $category;
    }


    public function __construct($item_id = null, $item_name = null, $category = null, $description = null, $manufacturer = null, $created_at = null){
        $this->item_id = $item_id;
        $this->item_name = $item_name;
        $this->category = $category;
        $this->description = $description;
        $this->manufacturer = $manufacturer;
        $this->created_at = $created_at;
    }


   public function create(){
    $conn = DatabaseConnection::getConnection();
    $sql = "INSERT INTO inventory_item (item_name, category, description, manufacturer, created_at)
            VALUES (:item_name, :category, :description, :manufacturer, :created_at)";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(":item_name", $this->item_name);
    $stmt->bindParam(":category", $this->category);
    $stmt->bindParam(":description", $this->description);
    $stmt->bindParam(":manufacturer", $this->manufacturer);
    $stmt->bindParam(":created_at", $this->created_at);


    return $stmt->execute();
}

    public function read(){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT item_name, category, description, manufacturer, created_at FROM inventory_item WHERE item_id = :item_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":item_id", $this->item_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $this->item_name = $result['item_name'];
            $this->category = $result['category'];
            $this->description = $result['description'];
            $this->manufacturer = $result['manufacturer'];
            $this->created_at = $result['created_at'];
            return $result;
        }
        return false;

    }


    public function update() {
        $conn = DatabaseConnection::getConnection();

        $sql = "UPDATE inventory_item SET 
                    item_name = :item_name,
                    category = :category,
                    description = :description,
                    manufacturer = :manufacturer,
                    created_at = :created_at
                WHERE item_id = :item_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':item_name', $this->item_name);
        $stmt->bindParam(':category', $this->category);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':manufacturer', $this->manufacturer);
        $stmt->bindParam(':created_at', $this->created_at);
        $stmt->bindParam(':item_id', $this->item_id);
        $stmt->execute();
        $success = $stmt-> execute();
        return $success;
    }
    
       


    public function delete(){
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM inventory_item WHERE item_id = :item_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":item_id", $this->item_id);
        
        return $stmt->execute();
    }
     public static function search($query) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM inventory_item WHERE item_name LIKE :q OR category LIKE :q OR manufacturer LIKE :q ORDER BY item_id";
        $stmt = $conn->prepare($sql);
        $likeQuery = "%" . $query . "%";
        $stmt->bindParam(":q", $likeQuery);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}