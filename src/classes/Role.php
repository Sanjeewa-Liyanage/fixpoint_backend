<?php
class Role extends Model{
public $role_id;
public $role_name;
public $description;

public function __construct($role_id = null, $role_name = null, $description = null){
    $this->role_id = $role_id;
    $this->role_name = $role_name;
    $this->description = $description;
}
public function create(){
$conn = DatabaseConnection::getConnection();
$sql = "INSERT INTO roles (role_name, description) VALUES (:role_name, :description)";
$stmt = $conn-> prepare($sql);
$stmt -> bindParam(":role_name", $this-> role_name);
$stmt -> bindParam(":description", $this-> description);
$success = $stmt -> execute();
return $success;


}
public function read(){
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT * FROM roles WHERE role_id = :role_id";
    $stmt = $conn->prepare($sql);
    $stmt -> bindParam(":role_id", $this->role_id);
    $stmt -> execute();
    $result = $stmt -> fetch(PDO::FETCH_ASSOC);
    $this->role_name = $result['role_name'];
    $this->description = $result['description'];
    return $result['role_id'] !== null; 
    
}
public function update(){
    $conn = DatabaseConnection::getConnection();
    $sql = 'UPDATE roles SET role_name = :role_name, description = :description WHERE role_id = :role_id';
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':role_id', $this->role_id);
    $stmt->bindParam(':role_name', $this->role_name);
    $stmt->bindParam(':description', $this->description);
    $stmt->execute();
    return $stmt->rowCount() > 0;
    
}
public function delete(){
    $conn = DatabaseConnection::getConnection();
    $sql = 'DELETE FROM roles WHERE role_id = :role_id';
    $stmt = $conn->prepare($sql);
    $stmt -> bindParam(':role_id', $this->role_id);
    $success = $stmt -> execute();
    return $success;


}
    public static function get_all($role_name, $limit,$page){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM roles WHERE role_name LIKE :role_name LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam("role_name", $role_name);
        $stmt->bindParam("limit", $limit);
        $stmt->bindParam("offset", $page, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt -> fetchAll(PDO::FETCH_ASSOC);
        if(count($rows) > 0){
            return [
                'message' => 'Data retrieved successfully',
                'data' => $rows,
                'page' => $page,
                'limit' => $limit,
            ];
        } else {
            return [
                'message' => 'No data found',
                'data' => [],
                'page' => $page,
                'limit' => $limit,
            ];
        }
    }
    public function get_by_user_id($user_id){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT r.role_id, r.role_name, r.description FROM roles AS r JOIN users AS u ON r.role_id = u.role_id WHERE u.user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->role_id = $result['role_id'];
            $this->role_name = $result['role_name'];
            $this->description = $result['description'];
            return true;
        } else {
            return false;
        }
    }

    

}