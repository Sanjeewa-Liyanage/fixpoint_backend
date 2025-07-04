<?php 
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtHandler{
    private static $secretKey = "fix_point_secret_key";
    private static $issuer = "fix_point_issuer";
    private static $audience =  "fix_point_audience";
    private static $issueAt;
    private static $expire;

    public static function generateToken($user){
        self::$issueAt = time();
        self::$expire = self::$issueAt + (60 * 60 * 24); // Token valid for 24 hours
        $payLoad = [
            "iss" => self::$issuer,
            "aud" => self::$audience,
            "iat" => self::$issueAt,
            "exp"=> self::$expire,
            "data" =>[
                 "id" => $user['user_id'] ?? null,
                "username" => $user['username'] ?? null,
                "email" => $user['email'] ?? null,
                "role_id" => $user['role_id'] ?? null,
                "role_name" =>$user['role']['role_name'] ?? null,
            ]
        ];
        return JWT::encode($payLoad, self::$secretKey, 'HS256');

    }    public static function decodeToken($token){
        try{
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            return [
                'valid' => true,
                'data' => (array)$decoded->data,
            ];
        }catch(Exception $ex){
            return[
                'valid'=> false,
                'data'=> $ex->getMessage(),
            ];
        }
    }
    
    /**
     * Extract and validate JWT token from Authorization header
     * 
     * @return array Token data or error information
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            return self::decodeToken($token);
        }
        
        return [
            'valid' => false,
            'data' => 'No token found in Authorization header'
        ];
    }
}