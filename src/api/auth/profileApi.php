<?php 
require_once 'src/utils/JwtHandler.php';
class ProfileApi extends ApiResourceBase{
     public function __construct()
    {
        $this->setRoles([
           
            "create" => ['admin'],
            "read" => ['admin'],
            "getAll" => ['admin','Technician','quality Checker'],
        ]);
    }
// create a new user profile
    public function create($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'create')) {
            return [
                'message' => 'Unauthorized: Admin access required',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['username', 'email', 'password', 'phone', 'profile_picture', 'role_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        
        $user = new User(null,$data['username'],$data['email'],$data['password'],$data['phone'],$data['profile_picture'],$data['role_id']);
        $success = $user->create();
        if ($success) {
            return [
                'message' => 'User created successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to create user',
                'status' => 'error'
            ];
            }
        }
// read a user profile by user_id
        public function read($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'read')) {
            return [
                'message' => 'Unauthorized: Admin access required',
                'status' => 'error'
            ];
        }   
        $missing = $this->validateFields($data, ['user_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
            
            $user = new User($data['user_id']);
            $result = $user->read();
            if ($result) {
                return [
                    'message' => 'User retrieved successfully',
                    'status' => 'success',
                    'data' => $user
                ];
            } else {
                return [
                    'message' => 'Failed to retrieve user',
                    'status' => 'error'
                ];
            }

        }

        

}
