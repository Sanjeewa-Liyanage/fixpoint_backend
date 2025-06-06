<?php
require_once 'src/utils/ApiResourceBase.php';

class UserApi extends ApiResourceBase{
    public function __construct(){
        $this -> setRoles([
            "login"=> ["user", "admin", null],
                                                                                                            

        ]);
    }

    public function login($data) {
    if (!isset($data['email'])) {
        return [ "status" => "error", "message" => "Email is required" ];
    }
    if (!isset($data['password'])) {
        return [ "status" => "error", "message" => "Password is required" ];
    }

    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT * FROM users WHERE email = :email";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':email', $data['email']);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        if ($data['password'] === $result['password']) {
            $sql = 'SELECT * FROM roles WHERE role_id = :role_id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':role_id', $result['role_id']);
            $stmt->execute();
            $role = $stmt->fetch(PDO::FETCH_ASSOC);

            $result['password'] = "";
            $result["role"] = $role;

            $token = JwtHandler::generateToken($result);

            return [
                "status" => "success",
                "message" => "Login successful",
                "token" => $token,
                "user" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Invalid password"
            ];
        }
    } else {
        return [
            "status" => "error",
            "message" => "User not found"
        ];
    }
}

    
}