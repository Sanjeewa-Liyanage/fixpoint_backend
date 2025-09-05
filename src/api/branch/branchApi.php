<?php
class BranchApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([

            "create_branch" => ["admin","technician"],
            "read_branch" => ["admin","technician"],
            "read_branch_by_id" => ["admin","technician"],
            "delete_branch" => ["admin"],
            "update_branch" => ["admin","technician"],
            "update_branch_name" => ["admin","technician"],
            "update_branch_contact_person" => ["admin","technician"],
            "update_branch_phone" => ["admin","technician"],
            "update_branch_email" => ["admin","technician"],
            "readAll_branches" => ["admin","technician"],
            "search_branch" => ["admin", "technician"],
            "getTellerScanner" =>["admin","technician"],
            "getChdm" => ["admin","technician"],
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
        $missing = $this -> validateFields($data,['client_name','name','address','latitude','longitude','location','contact_person','phone','email']);
        if(!empty($missing)){
           return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];

        }

        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT client_id FROM client WHERE name = :name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $data['client_name']);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($client) {
            $data['client_id'] = $client['client_id'];
        } else {
            return [
                "status" => "error",
                "message" => "Client not found."
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
    public function getByName($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'read_branch')){
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this->validateFields($data, ['name']);
        if(!empty($missing)){
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = Branch::getByName($data['name']);
        if ($branch) {
            return [
                "status" => "success",
                "message" => "Branch found.",
                "data" => $branch
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Branch not found."
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

        public function read_branch_by_id($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'read_branch_by_id')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
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
        $success = $branch->read();
        if ($success) {
            return [
                "status" => "success",
                "data" => $branch
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to read branch."
            ];
        }
    } 
    public function getChdm($data){
         $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'search_branch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this->validateFields($data, ['searchTerm']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $result = Branch::getChdmByBranch($data['searchTerm']);
        if ($result && count($result) > 0) {
            return [
                "status" => "success",
                "data" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No CHDM found for the given branch name."
            ];
        }
    }
    public function getTellerScanner($data){
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'search_branch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this->validateFields($data, ['searchTerm']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        // Get teller scanner details for branches matching the search term
        $result = Branch::getTellerScannerByBranch($data['searchTerm']);
        if ($result && count($result) > 0) {
            return [
                "status" => "success",
                "data" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No teller scanners found for the given branch name."
            ];
        }
    }
    


    public function search_branch($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'search_branch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }
        $missing = $this->validateFields($data, ['searchTerm']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $branch = new Branch();
        $result = $branch->searchBranch($data['searchTerm']);
        if (!$result) {
            return [
                "status" => "error",
                "message" => "No branches found matching the search term."
            ];
        }
         // Return the result in a structured format
         $result = array_map(function($branch) {
            return [
                'branch_id' => $branch['branch_id'],
                'client_id' => $branch['client_id'],
                'name' => $branch['name'],
                'address' => $branch['address'],
                'latitude' => $branch['latitude'],
                'longitude' => $branch['longitude'],
                'location' => $branch['location'],
                'contact_person' => $branch['contact_person'],
                'phone' => $branch['phone'],
                'email' => $branch['email']
            ];
        }, $result);
        if ($result && count($result) > 0) {
            return [
                "status" => "success",
                "data" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No branches found matching the search term."
            ];
        }
    }


    public function readAll_branches($data = []) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'readAll_branches')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or technician access required"
            ];
        }

        // Get pagination parameters with defaults
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $limit = isset($data['limit']) ? max(1, min(100, intval($data['limit']))) : 10;

        $result = Branch::getAllBranchDetails($page, $limit);

        if ($result && $result['data'] && count($result['data']) > 0) {
            return [
                "status" => "success",
                "data" => $result['data'],
                "count" => count($result['data']),
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => $result['page'] < $result['total_pages'],
                    "has_prev" => $result['page'] > 1
                ]
            ];
        } else {
            return [
                "status" => "success",
                "data" => [],
                "count" => 0,
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "message" => "No branches found.",
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => false,
                    "has_prev" => false
                ]
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

    public function update_branch($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'update_branch')){
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }

        // First, get the existing branch data
        $branch = new Branch($data['branch_id']);
        $success = $branch->read();
        
        if (!$success) {
            return [
                "status" => "error",
                "message" => "Branch not found."
            ];
        }

        // Update only the fields that are provided in the request
        if (isset($data['client_id'])) {
            $branch->client_id = $data['client_id'];
        }
        if (isset($data['name'])) {
            $branch->name = $data['name'];
        }
        if (isset($data['address'])) {
            $branch->address = $data['address'];
        }
        if (isset($data['latitude'])) {
            $branch->latitude = $data['latitude'];
        }
        if (isset($data['longitude'])) {
            $branch->longitude = $data['longitude'];
        }
        if (isset($data['location'])) {
            $branch->location = $data['location'];
        }
        if (isset($data['contact_person'])) {
            $branch->contact_person = $data['contact_person'];
        }
        if (isset($data['phone'])) {
            $branch->phone = $data['phone'];
        }
        if (isset($data['email'])) {
            $branch->email = $data['email'];
        }

        $updateSuccess = $branch->update();
        
        if ($updateSuccess) {
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


