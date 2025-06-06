<?php
class ApiResourceBase{
    private $roles;
    protected function setRoles($roles){
        $this -> roles = $roles;
    }
    public function checkRoles($role,$action){
        if(isset($this-> roles[$action])){
            if(in_array($role,$this-> roles[$action])){
                return true;
            }
        }
        return false;
    }
    protected function validateFields($data,$requiredFields){
        $missingFields =[];
        foreach($requiredFields as $field){
            if(!isset($data[$field])){
                $missingFields[] = $field;
            }
        }
        return $missingFields;
    }
    public function getAuthenticatedUser(){
        $headers = getallheaders();
        if(isset($headers["Authorization"])){
            $authHeader = $headers["Authorization"];
            if(preg_match('/Bearer\s(\S+)/', $authHeader, $matches)){
                $token = $matches[1];
                $decodedToken = JwtHandler::decodeToken($token);
                if($decodedToken['valid']){
                    return $decodedToken['data'];
                } else {
                    return null; // Invalid token
                }
            }
        }
    }
}