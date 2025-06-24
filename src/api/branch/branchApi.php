<?php
class BranchApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([

            "create_branch" => ["admin","technician"],
            "read_branch" => ["admin","technician"],
            "delete_branch" => ["admin"],
            "update_branch_name" => ["admin"],
            "update_branch_contact_person" => ["admin"],
            "update_branch_phone" => ["admin"],
            "update_branch_email" => ["admin"],
        ]);

    }
    public function create_branch($data){
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return[
                "status"=>"error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'],'create_branch')){
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this -> validateFields($data,['client_id','name','address','latitude','longitude','location','contact_person','phone','email']);
        if(!empty($missing)){
           return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];

        }
        $branch = new Branch(null,$data['client_id'],$data['name'],$data['address'],$data['latitude'],$data['longitude'],$data['location'],$data['contact_person'],$data['phone'],$data['email']);
        $success = $branch -> create();

        if ($success) {
            return [
                "status" => "success",
                "message" => "branch created successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to create branch."
            ];
        }
    }

    public function read_branch($data){
        // Ensure $data is always an array
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (!is_array($data)) {
            return [
                "status" => "error",
                "message" => "Invalid request data format."
            ];
        }
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'],'read_branch')){
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this->validateFields($data, ['client_id']);
        if(!empty($missing)){
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branches = Branch::getByClientId($data['client_id']);
        if ($branches && count($branches) > 0) {
            return [
                "status" => "success",
                "message" => "Branches retrieved successfully.",
                "data" => $branches
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No branches found for this client."
            ];
        }
    }
    public function delete_branch($data){

        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'],"delete_branch")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['branch_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = new Branch($data['branch_id']);
        $success = $branch->delete();  
        if ($success) {
            return [
                "status" => "success",
                "message" => "Branch deleted successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to delete branch."
            ];
        }

    }
    public function update_branch_name($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error", 
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user["role_name"], "update_branch_name")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['branch_id', 'name']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = new Branch();
        $success = $branch->updateName($data['branch_id'], $data['name']);
        if ($success) {
            return ["status" => "success", "message" => "Branch name updated successfully."];
        } else {
            return ["status" => "error", "message" => "Failed to update branch name."];
        }
    }


/*public function update_branch($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "status" => "error",
            "message" => "Invalid authentication token"
        ];
    }
    $missing = $this->validateFields($data, ['branch_id', 'contact_person', 'phone', 'email']);
    if (!empty($missing)) {
        return [
            "status" => "error",
            "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
        ];
    }

    $branch = new Branch($data['branch_id'], null, $data['name'], null, null, null, null, $data['contact_person'], $data['phone'], $data['email']);
    $success = $branch->update();
    if ($success) {
        return [
            "status" => "success",
            "message" => "Branch updated successfully."
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to update branch."
        ];
    }
}*/

public function update_branch_contact_person($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'update_branch_contact_person')){
            return [
                "status" => "error",
                "message" => "Forbidden",
                "role" => $user['role_name'],
                "action" => "update_branch_contact_person"
            ];
        }
        $missing = $this->validateFields($data, ['branch_id', 'contact_person']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = new Branch();
        $success = $branch->updateContactPerson($data['branch_id'], $data['contact_person']);
        if ($success) {
            return [
                "status" => "success",
                "message" => "Branch contact person updated successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to update contact person."
            ];
        }
    }

    public function update_branch_phone($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'update_branch_phone')){
            return [
                "status" => "error",
                "message" => "Forbidden",
                "role" => $user['role_name'],
                "action" => "update_branch_phone"
            ];
        }
        $missing = $this->validateFields($data, ['branch_id', 'phone']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = new Branch();
        $success = $branch->updatePhone($data['branch_id'], $data['phone']);
        if ($success) {
            return [
                "status" => "success",
                "message" => "Branch phone updated successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to update phone."
            ];
        }
    }



public function update_branch_email($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "status" => "error",
            "message" => "Invalid authentication token"
        ];
    }
    if (!$this->checkRoles($user["role_name"], "update_branch_email")) {
        return [
            "status" => "error",
            "message" => "Unauthorized: Admin access required"
        ];
    }
    $missing = $this->validateFields($data, ['branch_id', 'email']);
    if (!empty($missing)) {
        return [
            "status" => "error",
            "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
        ];
    }
    $branch = new Branch();
    $success = $branch->updateEmail($data['branch_id'], $data['email']);
    if ($success) {
        return [
            "status" => "success", 
            "message" => "Branch email updated successfully."
       ];
    } else {
        return [
            "status" => "error", "message" => "Failed to update email."];
    }
}




}


