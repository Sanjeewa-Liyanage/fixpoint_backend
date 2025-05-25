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
}