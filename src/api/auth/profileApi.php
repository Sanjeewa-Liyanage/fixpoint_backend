<?php 
require_once 'src/utils/JwtHandler.php';
class ProfileApi extends ApiResourceBase{
     public function __construct()
    {
        $this->setRoles([
           
            "create" => ['admin'],
            "get" => ['admin'],
            "getAll" => ['admin'],
            'update'=> ['admin','Technician','Quality_Checker'],
            'delete'=> ['admin'],
            'getAllUsers' => ['admin']
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
        // Only require user fields, not role_id
        $missing = $this->validateFields($data, ['username', 'email', 'password', 'phone', 'profile_picture']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        // Do not set role_id at creation
        $user = new User(null, $data['username'], $data['email'], $data['password'], $data['phone'], $data['profile_picture']);
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
        public function get($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get')) {
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
// get all user profiles
public function getAll($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'getAll')) {
            return [
                'message' => 'Unauthorized: Admin access required',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['email', 'page', 'limit']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        // Ensure limit and page are integers for security
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $email = isset($data['email']) ? $data['email'] : '';
        $res = User::get_all($email, $limit, $page);
        return [
            'message' => 'Users retrieved successfully',
            'status' => 'success',
            'data' => $res
        ];
    }
    public function update($data){
        $user = $this-> getAuthenticatedUser();
        if(!$user){
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'update')) {
            return [
                'message' => 'Unauthorized: Admin access required',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data,['user_id', 'username', 'email', 'phone', 'profile_picture']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        $user = new User($data['user_id'], $data['username'], $data['email'], null, $data['phone'], $data['profile_picture']);
        $success = $user->update();
        if ($success) {
            return [
                'message' => 'User updated successfully',
                'status' => $success
            ];
        } else {
            return [
                'message' => 'Failed to update user',
                'status' => 'error'
            ];
        }


    }
    public function delete($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'delete')) {
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
        $success = $user->delete();
        if ($success) {
            return [
                'message' => 'User deleted successfully',
                'status' => $success
            ];
        } else {
            return [
                'message' => 'Failed to delete user',
                'status' => 'error'
            ];
        }
 
 
    }

    public function getAllUsers(){
        $user = $this-> getAuthenticatedUser();

        if(!$user){
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'getAll')) {
            return [
                'message' => 'Unauthorized: Admin access required',
                'status' => 'error'
            ];
        }
        $users = User::get_All_Users();
        if ($users) {
            return [
                'message' => 'Users retrieved successfully',
                'status' => 'success',
                'data' => $users
            ];
        } else {
            return [
                'message' => 'Failed to retrieve users',
                'status' => 'error'
            ];
        }
    }

}
