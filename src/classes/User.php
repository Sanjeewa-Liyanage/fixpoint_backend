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
        $sql = "INSERT INTO users (username, email, password, phone, profile_picture, role_id) VALUES (:username, :email, :password, :phone, :profile_picture, :role_id)";
        $stmt = $conn->prepare($sql);
        $stmt -> bindParam(":email", $this->email);
        $stmt -> bindParam(":username", $this->username);
        $stmt -> bindParam(":password", $this->password);
        $stmt -> bindParam(":phone", $this->phone);
        $stmt -> bindParam(":profile_picture", $this->profile_picture);
        $stmt -> bindParam(":role_id", $this->role_id);
        $success = $stmt -> execute();
        return $success;
       
    }

    public function delete() {
        // Implement delete logic here
        // Example: Delete user from database by $this->id
        return true;
    }

    public function read() {
        // Implement read logic here
        // Example: Fetch user from database by $this->id
        return true;
    }
  

    public function update() {
       
        return true;
    }
}



