<?php
class Branch extends Model{
    public $branch_id;
    public $client_id;
    public $name;
    public $address;
    public $latitude;
    public $longitude;
    public $location;
    public $contact_person;
    public $phone;
    public $email;

    public function __construct($branch_id = null, $client_id = null, $name = null, $address = null, $latitude = null, $longitude = null, $location = null, $contact_person = null, $phone = null, $email = null){
        $this->branch_id = $branch_id;
        $this->client_id = $client_id;
        $this->name = $name;
        $this->address = $address;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->location = $location;
        $this->contact_person = $contact_person;
        $this->phone = $phone;
        $this->email = $email;
    }

    public function create(){
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO branch (client_id, name, address, latitude, longitude, location, contact_person, phone, email) VALUES (:client_id, :name, :address, :latitude, :longitude, :location, :contact_person, :phone, :email)";
        $stmt = $conn-> prepare($sql);
        $stmt -> bindParam(":client_id", $this->client_id);
        $stmt -> bindParam(":name", $this->name);
        $stmt -> bindParam(":address", $this->address);
        $stmt -> bindParam(":latitude", $this->latitude);
        $stmt -> bindParam(":longitude", $this->longitude);
        $stmt -> bindParam(":location", $this->location);
        $stmt -> bindParam(":contact_person", $this->contact_person);
        $stmt -> bindParam(":phone", $this->phone);
        $stmt -> bindParam(":email", $this->email);
        $success = $stmt -> execute();
        return $success;
          

    }
    public function read(){

    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT * FROM branch WHERE branch_id = :branch_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(":branch_id", $this->branch_id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        $this->client_id = $result['client_id'];
        $this->name = $result['name'];
        $this->address = $result['address'];
        $this->latitude = $result['latitude'];
        $this->longitude = $result['longitude'];
        $this->location = $result['location'];
        $this->contact_person = $result['contact_person'];
        $this->phone = $result['phone'];
        $this->email = $result['email'];
        return true;
    }
    return false;  
       
    }

    public static function getAllBranchDetails(){

        $conn = DatabaseConnection::getConnection();
        $sql ="SELECT b.*, c.name AS client_name FROM branch b JOIN client c ON b.client_id = c.client_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;

    }


    


    public function delete(){
        $conn = DatabaseConnection::getConnection();
        $sql = 'DELETE FROM branch WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt ->bindparam(':branch_id', $this->branch_id);
        $success = $stmt -> execute();
        return $success;


        
    }

    static public function getByName($name) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT branch_id FROM branch WHERE name = :name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $name);
        $stmt->execute();
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        return $branch;
    }


    
    public function update(){
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE branch SET 
                client_id = :client_id,
                name = :name, 
                address = :address,
                latitude = :latitude,
                longitude = :longitude,
                location = :location,
                contact_person = :contact_person, 
                phone = :phone, 
                email = :email 
                WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $this->branch_id);
        $stmt->bindParam(':client_id', $this->client_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':latitude', $this->latitude);
        $stmt->bindParam(':longitude', $this->longitude);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':contact_person', $this->contact_person);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':email', $this->email);
        $success = $stmt->execute();
        return $success;
    }
    public static function getByClientId($client_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM branch WHERE client_id = :client_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":client_id", $client_id);
        $stmt->execute();
        $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $branches;
    }

    public static function getById($branch_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM branch WHERE branch_id = :branch_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":branch_id", $branch_id);
        $stmt->execute();
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        return $branch;
    }

    public static function getByName($name) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM branch WHERE name = :name";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":name", $name);
        $stmt->execute();
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        return $branch;
    }




    public function updateName($branch_id, $name) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE branch SET name = :name WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->bindParam(':name', $name);
        return $stmt->execute();
    }

    public function updateContactPerson($branch_id, $contact_person) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE branch SET contact_person = :contact_person WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->bindParam(':contact_person', $contact_person);
        return $stmt->execute();
    }

    public function updatePhone($branch_id, $phone) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE branch SET phone = :phone WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->bindParam(':phone', $phone);
        return $stmt->execute();
    }

    public function updateEmail($branch_id, $email) {
        $conn = DatabaseConnection::getConnection();
        $sql = 'UPDATE branch SET email = :email WHERE branch_id = :branch_id';
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':branch_id', $branch_id);
        $stmt->bindParam(':email', $email);
        return $stmt->execute();
    }

}
