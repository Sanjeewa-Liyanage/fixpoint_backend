<?php
 class Service_Reporting extends Model {
    public $service_id;
    public $branch_id;
    public $client_id;
    public $user_id;
    public $device_type;
    public $service_date;
    public $service_type;
    public $service_notes;
    public $created_at;
    public $teller_scanner_serial;
    public $chdm_serial;

    public function __construct($service_id = null, $branch_id = null, $client_id = null, $user_id = null, $device_type = null, $service_date = null, $service_type = null, $service_notes = null, $created_at = null, $teller_scanner_serial = null, $chdm_serial = null) {
        $this->service_id = $service_id;
        $this->branch_id = $branch_id;
        $this->client_id = $client_id;
        $this->user_id = $user_id;
        $this->device_type = $device_type;
        $this->service_date = $service_date;
        $this->service_type = $service_type;
        $this->service_notes = $service_notes;
        $this->created_at = $created_at;
        $this->teller_scanner_serial = $teller_scanner_serial;
        $this->chdm_serial = $chdm_serial;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO service (branch_id, client_id, user_id, device_type, service_date, service_type, service_notes, created_at, teller_scanner_serial, chdm_serial)
                VALUES (:branch_id, :client_id, :user_id, :device_type, :service_date, :service_type, :service_notes, :created_at, :teller_scanner_serial, :chdm_serial)";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(":branch_id", $this->branch_id);
        $stmt->bindParam(":client_id", $this->client_id);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":device_type", $this->device_type);
        $stmt->bindParam(":service_date", $this->service_date);
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":service_notes", $this->service_notes);
        $stmt->bindParam(":created_at", $this->created_at);
        $stmt->bindParam(":teller_scanner_serial", $this->teller_scanner_serial);
        $stmt->bindParam(":chdm_serial", $this->chdm_serial);

        $success = $stmt->execute();
        return $success;
    }
    public function search($keyword) {
        $conn = DatabaseConnection::getConnection();
       $sql = "SELECT * FROM service WHERE 
    CAST(branch_id AS TEXT) LIKE :keyword OR 
    CAST(client_id AS TEXT) LIKE :keyword OR
    CAST(user_id AS TEXT) LIKE :keyword OR
    device_type LIKE :keyword OR
    CAST(service_date AS TEXT) LIKE :keyword OR
    service_type LIKE :keyword OR
    service_notes LIKE :keyword OR
    CAST(created_at AS TEXT) LIKE :keyword OR
    teller_scanner_serial LIKE :keyword OR
    chdm_serial LIKE :keyword";

        $stmt = $conn->prepare($sql);
        $likeKeyword = '%' . $keyword . '%';
        $stmt->bindParam(':keyword', $likeKeyword);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function read(){}
   
    public function update_service_fields( $data) {
        if (!isset($this->service_id)) {
            return false;
        }

        $fields = [];
        $params = [':service_id' => $this->service_id];

        if(isset($data['device_type'])) {
            $fields[] = 'device_type = :device_type';
            $params[':device_type'] = $data['device_type'];
        }

        if(isset($data['service_type'])) {
            $fields[] = 'service_type = :service_type';
            $params[':service_type'] = $data['service_type'];
        }
      
        if(isset($data['service_notes'])) {
            $fields[] = 'service_notes = :service_notes';
            $params[':service_notes'] = $data['service_notes'];
        }

        if(isset($data['teller_scanner_serial'])) {
            $fields[] = 'teller_scanner_serial = :teller_scanner_serial';
            $params[':teller_scanner_serial'] = $data['teller_scanner_serial'];
        }

        if(isset($data['chdm_serial'])) {
            $fields[] = 'chdm_serial = :chdm_serial';
            $params[':chdm_serial'] = $data['chdm_serial'];
        }

        if(empty($fields)) {
            return false; // No fields to update
        }
        
        $sql = "UPDATE service SET " . implode(', ', $fields) . " WHERE service_id = :service_id";

        $conn = DatabaseConnection::getConnection();
        $stmt = $conn->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        return $stmt->execute();

    }   

     public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM service WHERE service_id = :service_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':service_id', $this->service_id);
        $success = $stmt->execute();
        return $success;
    }
    public function update(){}
    

}