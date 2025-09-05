<?php 
require_once 'src/utils/ApiResourceBase.php';
require_once 'src/utils/imports.php';
require_once 'src/utils/defaultpassword.php'; // Explicit include for DefaultPasswordGenerator

use Fixpoint\Utils\AzureEmailService;

class ProfileApi extends ApiResourceBase{
     public function __construct()
    {
        $this->setRoles([
           
            "create" => ['admin'],
            "get" => ['admin'],
            "getAll" => ['admin'],
            'update'=> ['admin','Technician','Quality_Checker'],
            'delete'=> ['admin'],
            'getAllUsers' => ['admin'],
            'getProfile' => ['admin', 'user', 'Technician', 'Quality_Checker']
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
        // Remove password from required fields as we'll generate it
        $missing = $this->validateFields($data, ['username', 'email', 'phone', 'profile_picture']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        
        // Generate a default password
        $defaultPassword = DefaultPasswordGenerator::generate(12);
        
        // Create user with generated password
        $newUser = new User(null, $data['username'], $data['email'], $defaultPassword, $data['phone'], $data['profile_picture']);
        $success = $newUser->create();
        
        if ($success) {
            // Configure Azure Email Service
            $connectionString = "endpoint=https://fixpoit-mailler.unitedstates.communication.azure.com/;accesskey=DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx";
            $senderAddress = "DoNotReply@1150820c-c077-40e5-bf54-90e4e6adcb7e.azurecomm.net";
            AzureEmailService::configure($connectionString, $senderAddress);
            
            // Send default password via email
            try {
                $emailResult = AzureEmailService::sendDefaultPasswordEmail($data['email'], $defaultPassword);
                
                return [
                    'message' => 'User created successfully and default password sent to email',
                    'status' => 'success',
                    'email_sent' => true
                ];
            } catch (Exception $e) {
                // User was created but email failed - still return success
                return [
                    'message' => 'User created successfully but failed to send email: ' . $e->getMessage(),
                    'status' => 'success',
                    'email_sent' => false
                ];
            }
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

    // Get current user's profile using ID from JWT token
    public function getProfile($data = null){
        $authenticatedUser = $this->getAuthenticatedUser();
        if (!$authenticatedUser) {
            return [
                'message' => 'Invalid authentication token',
                'status' => 'error'
            ];
        }
        
        if(!$this->checkRoles($authenticatedUser['role_name'], 'getProfile')) {
            return [
                'message' => 'Unauthorized access',
                'status' => 'error'
            ];
        }
        
        // Extract user ID from the authenticated user token - JWT stores it as 'id'
        $userId = $authenticatedUser['id'];
        
        if (!$userId) {
            return [
                'message' => 'User ID not found in token',
                'status' => 'error'
            ];
        }
        
        $user = new User();
        $result = $user->getById($userId);
        
        if ($result) {
            // Remove sensitive information before returning
            $userProfile = [
                'user_id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'profile_picture' => $user->profile_picture,
                'role_id' => $user->role_id
            ];
            
            return [
                'message' => 'Profile retrieved successfully',
                'status' => 'success',
                'data' => $userProfile
            ];
        } else {
            return [
                'message' => 'User profile not found',
                'status' => 'error'
            ];
        }
    }

}
