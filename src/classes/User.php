<?php

class User extends Model{
    public $id;
    public $username;
    public $email;
    public $password;
    public $phone;
    public $profile_picture;
    public $role_id;

    public function __construct($id = null,$username = null,$email = null,$password = null,$phone = null,$profile_picture = null,$role_id = null){
    $this->id = $id;
    $this->username = $username;
    $this->email = $email;
    $this->password = $password;
    $this->phone = $phone;
    $this->profile_picture = $profile_picture;
    $this->role_id = $role_id;
    }
     public function create(){
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO users (username, email, password, phone, profile_picture) VALUES (:username, :email, :password, :phone, :profile_picture)";
        $stmt = $conn->prepare($sql);
        $stmt -> bindParam(":email", $this->email);
        $stmt -> bindParam(":username", $this->username);
        $stmt -> bindParam(":password", $this->password);
        $stmt -> bindParam(":phone", $this->phone);
        $stmt -> bindParam(":profile_picture", $this->profile_picture);
        $success = $stmt -> execute();
        return $success;
       
    }

   

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql ="SELECT u.user_id, u.username, u.email, u.phone, u.profile_picture, u.role_id, role.role_name FROM users AS u LEFT JOIN roles AS role ON u.role_id = role.role_id WHERE u.user_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return false; // User not found
        }
        
        $this->username = $result['username'];
        $this->email = $result['email'];
        $this->phone = $result['phone'];
        $this->profile_picture = $result['profile_picture'];
        $this->role_id = $result['role_id'];
        return true; 
    }


  
    public function get_user_by_email(){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return false; // User not found
        }
        
        $this->id = $result['user_id'];
        $this->username = $result['username'];
        $this->phone = $result['phone'];
        $this->profile_picture = $result['profile_picture'];
        $this->role_id = $result['role_id'];
        return true;
        
    }
    public static function get_all($email, $limit, $page){
        $conn = DatabaseConnection::getConnection();
        $limit = (int)$limit;
        $page = (int)$page;
        $offset = ($page - 1) * $limit;
        $sql = "SELECT u.user_id, u.username, u.email, u.phone, u.profile_picture, u.role_id, role.role_name\n            FROM users AS u\n            JOIN roles AS role ON u.role_id = role.role_id\n            WHERE u.email LIKE :email\n            LIMIT $limit OFFSET $offset";
        $stmt = $conn->prepare($sql);
        $email = "%". $email ."%";
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function get_All_Users(){
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT u.user_id, u.username, u.email, u.phone, u.profile_picture, u.role_id, role.role_name 
                FROM users AS u 
                LEFT JOIN roles AS role ON u.role_id = role.role_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }
    
    
    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE users SET username = :username, email = :email, phone = :phone, profile_picture = :profile_picture WHERE user_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->id);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':profile_picture', $this->profile_picture);

       
        return $stmt->execute();
    }
     public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = 'DELETE FROM users WHERE user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    public function assign_role($role_id) {
        $conn = DatabaseConnection::getConnection();
        $sql ='UPDATE users SET role_id = :role_id WHERE user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt -> bindParam(':role_id', $role_id);
        $stmt -> bindParam(':user_id', $this->id);
        $success = $stmt -> execute();
        return $success;
    }

}




