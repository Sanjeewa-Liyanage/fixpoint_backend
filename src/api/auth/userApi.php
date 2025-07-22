<?php
require_once 'src/utils/ApiResourceBase.php';

class UserApi extends ApiResourceBase{
    public function __construct(){
        $this -> setRoles([
            "login"=> ["user", "admin", null],
            "send_verification"=> ["user", "admin", null],                                                                                           
            "verify_otp"=> ["user", "admin", null],
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
    public function send_verification($data){
        $missing = $this->validateFields($data, ['email']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        $conn = DatabaseConnection::getConnection();
        $sql = 'SELECT * FROM users WHERE email = :email';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result){
            $to = $data['email'];
            $subject = "Password Reset Otp";
            $otp = rand(100000, 999999);
            $message = "Your OTP for password reset is: " . $otp;
            $headers = "From: no-reply@example.com";
            
            $insert_sql = 'INSERT INTO verification_code (user_id, otp, email) VALUES (:user_id, :otp, :email)';
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bindValue(':user_id', $result['user_id']);
            $insert_stmt->bindValue(':otp', $otp);
            $insert_stmt->bindValue(':email', $result['email']);
            $insert_stmt->execute();

            return [
                'message' => 'Verification code sent to your email'." ". $data['email'] ." ". $otp,
                'status' => 'success'
                //todo: add email server here 
            ];
            
        } else {
            return [
                'message' => 'User not found',
                'status' => 'error'
            ];



        }
    }

    public function verify_otp($data){
        $missing = $this->validateFields($data, ['email', 'otp']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        $conn = DatabaseConnection::getConnection();
        $sql = 'SELECT * FROM users WHERE email = :email';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if($user){
            // Fetch the latest OTP for this user
            $sql = 'SELECT * FROM verification_code WHERE user_id = :user_id AND email = :email ORDER BY id DESC LIMIT 1';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $user['user_id']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->execute();
            $otpRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if($otpRow){
                // Trim and compare as string
                $inputOtp = trim((string)$data['otp']);
                $dbOtp = trim((string)$otpRow['otp']);
                if($inputOtp === $dbOtp){
                    return [
                        'message' => 'Otp verified successfully',
                        'status' => 'success'
                    ];
                } else {
                    return [
                        'message' => 'Invalid Otp',
                        'status' => 'error',
                        'debug' => [
                            'inputOtp' => $inputOtp,
                            'dbOtp' => $dbOtp
                        ]
                    ];
                }
            } else {
                return [
                    'message' => 'No OTP found for this user',
                    'status' => 'error'
                ];
            }
        } else {
            return [
                'message' => 'User not found',
                'status' => 'error'
            ];
        }
    }

    
    
}