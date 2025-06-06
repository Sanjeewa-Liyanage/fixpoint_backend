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
public function update(){}
public function delete(){}


}