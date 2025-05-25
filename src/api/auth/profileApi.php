<?php 
class ProfileApi extends ApiResourceBase{
     public function __construct()
    {
        $this->setRoles([
           
            "create" => ['admin','owner']
        ]);
    }
    public function create($data){
        

        if (!isset($data['username']) || !isset($data['email']) || !isset($data['password']) || !isset($data['phone'])|| !isset($data['profile_picture']) || !isset($data['role_id'])) {
            return [
                'message' => 'Invalid Request. username, email, password, phone, profile_picture, and role_id must be provided.',
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
}