<?php
require_once 'src/utils/ApiResourceBase.php';
require_once 'src/utils/imports.php';
require_once 'src/utils/AzureEmailService.php'; // added email service

use Fixpoint\Utils\AzureEmailService; // import namespaced class

class UserApi extends ApiResourceBase{
    public function __construct(){
        $this -> setRoles([
            "login"=> ["user", "admin", null],
            "send_verification"=> ["user", "admin", null],                                                                                           
            "verify_otp"=> ["user", "admin", null],
            "search_users"=> ["admin"],
            "request_password_reset"=> ["user", "admin", null],
            "verify_reset_code"=> ["user", "admin", null],
            "reset_password"=> ["user", "admin", null],
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
    public function send_verification($data)
    {
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
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $to = $data['email'];
            $otp = rand(100000, 999999);

            // Save OTP to your database as before
            $insert_sql = 'INSERT INTO verification_code (user_id, otp, email) VALUES (:user_id, :otp, :email)';
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bindValue(':user_id', $user['user_id']);
            $insert_stmt->bindValue(':otp', $otp);
            $insert_stmt->bindValue(':email', $user['email']);
            $insert_stmt->execute();
            
            // --- THIS IS THE CORRECTED PART ---

            // 1. Use the FULL connection string with "endpoint="
            $connectionString = "endpoint=https://fixpoit-mailler.unitedstates.communication.azure.com/;accesskey=DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx";

            // 2. Use the SENDER ADDRESS with the GUID-based domain from your working JS sample
            $senderAddress = "DoNotReply@1150820c-c077-40e5-bf54-90e4e6adcb7e.azurecomm.net";

            // Configure the service with the correct values
            AzureEmailService::configure($connectionString, $senderAddress);

            // Send the email with username
            $result = AzureEmailService::sendOtpEmail($to, (string)$otp, $user['username']);

            return [
                'message' => 'Verification code sent to your email ' . $data['email'] . '. OTP: ' . $otp, // For debugging
                'status' => 'success',
                
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

    public function search_users($data) {
        $missing = $this->validateFields($data, ['keyword']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $searchResults = User::searchUser($data['keyword']);
        
        if (!empty($searchResults)) {
            return [
                'message' => 'Users found',
                'status' => 'success',
                'data' => $searchResults
            ];
        } else {
            return [
                'message' => 'No users found matching the search criteria',
                'status' => 'success',
                'data' => []
            ];
        }
    }

    public function request_password_reset($data) {
        $missing = $this->validateFields($data, ['email']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $conn = DatabaseConnection::getConnection();
        
        // Check if user exists
        $sql = 'SELECT user_id, username, email FROM users WHERE email = :email';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'message' => 'User not found with this email address',
                'status' => 'error'
            ];
        }

        // Generate 6-digit reset code
        $resetCode = rand(100000, 999999);

        // Save reset code to verification_code table
        $insert_sql = 'INSERT INTO verification_code (user_id, otp, email, created_at) VALUES (:user_id, :otp, :email, NOW())';
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bindValue(':user_id', $user['user_id']);
        $insert_stmt->bindValue(':otp', $resetCode);
        $insert_stmt->bindValue(':email', $user['email']);
        
        if ($insert_stmt->execute()) {
            // Configure Azure Email Service
            $connectionString = "endpoint=https://fixpoit-mailler.unitedstates.communication.azure.com/;accesskey=DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx";
            $senderAddress = "DoNotReply@1150820c-c077-40e5-bf54-90e4e6adcb7e.azurecomm.net";
            AzureEmailService::configure($connectionString, $senderAddress);

            // Send password reset email
            $result = AzureEmailService::sendPasswordResetEmail($user['email'], (string)$resetCode, $user['username']);

            return [
                'message' => 'Password reset code sent to your email address',
                'status' => 'success',
                'debug_code' => $resetCode // Remove this in production
            ];
        } else {
            return [
                'message' => 'Failed to generate reset code. Please try again.',
                'status' => 'error'
            ];
        }
    }

    public function verify_reset_code($data) {
        $missing = $this->validateFields($data, ['email', 'reset_code']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $conn = DatabaseConnection::getConnection();
        
        // Check if user exists
        $sql = 'SELECT user_id FROM users WHERE email = :email';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'message' => 'User not found',
                'status' => 'error'
            ];
        }

        // Fetch the latest reset code for this user (within last 15 minutes)
        $sql = 'SELECT * FROM verification_code 
                WHERE user_id = :user_id AND email = :email 
                AND created_at >= NOW() - INTERVAL \'15 minutes\'
                ORDER BY created_at DESC LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':user_id', $user['user_id']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $codeRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$codeRow) {
            return [
                'message' => 'No valid reset code found or code has expired',
                'status' => 'error'
            ];
        }

        // Compare reset codes
        $inputCode = trim((string)$data['reset_code']);
        $dbCode = trim((string)$codeRow['otp']);
        
        if ($inputCode === $dbCode) {
            return [
                'message' => 'Reset code verified successfully',
                'status' => 'success',
                'reset_token' => base64_encode($data['email'] . '|' . $inputCode . '|' . time())
            ];
        } else {
            return [
                'message' => 'Invalid reset code',
                'status' => 'error'
            ];
        }
    }

    public function reset_password($data) {
        $missing = $this->validateFields($data, ['email', 'reset_code', 'new_password']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Validate password strength
        if (strlen($data['new_password']) < 6) {
            return [
                'message' => 'Password must be at least 6 characters long',
                'status' => 'error'
            ];
        }

        $conn = DatabaseConnection::getConnection();
        
        // Check if user exists
        $sql = 'SELECT user_id, username FROM users WHERE email = :email';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $data['email']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'message' => 'User not found',
                'status' => 'error'
            ];
        }

        // Verify reset code one more time (within last 15 minutes)
        $sql = 'SELECT id FROM verification_code 
                WHERE user_id = :user_id AND email = :email AND otp = :reset_code
                AND created_at >= NOW() - INTERVAL \'15 minutes\'
                ORDER BY created_at DESC LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':user_id', $user['user_id']);
        $stmt->bindValue(':email', $data['email']);
        $stmt->bindValue(':reset_code', trim((string)$data['reset_code']));
        $stmt->execute();
        $codeRow = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$codeRow) {
            return [
                'message' => 'Invalid or expired reset code',
                'status' => 'error'
            ];
        }

        // Update user password
        $sql = 'UPDATE users SET password = :new_password WHERE user_id = :user_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':new_password', $data['new_password']);
        $stmt->bindValue(':user_id', $user['user_id']);
        
        if ($stmt->execute()) {
            // Delete used verification codes for this user
            $sql = 'DELETE FROM verification_code WHERE user_id = :user_id';
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':user_id', $user['user_id']);
            $stmt->execute();

            return [
                'message' => 'Password reset successfully. You can now login with your new password.',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to update password. Please try again.',
                'status' => 'error'
            ];
        }
    }
    
}