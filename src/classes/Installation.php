<?php
    class Installation extends model{
        public $installation_id;
        public $chdm_id;
        public $branch_id;
        public $technician_id;
        public $status;
        public $date;
        public $completion_date;
        public $software_version;
        public $ip_address;
        public $notes;

        public function __construct($installation_id = null, $chdm_id = null, $branch_id = null, $technician_id = null, $status = null, $date = null, $completion_date = null, $software_version = null, $ip_address = null, $notes = null){
            $this->installation_id = $installation_id;
            $this->chdm_id = $chdm_id;
            $this->branch_id = $branch_id;
            $this->technician_id = $technician_id;
            $this->status = $status;
            $this->date = $date;
            $this->completion_date = $completion_date;
            $this->software_version = $software_version;
            $this->ip_address = $ip_address;
            $this->notes = $notes;
        }

        public function create(){
            $conn = DatabaseConnection::getConnection();
            $sql = "INSERT INTO installation (chdm_id, branch_id, technician_id, status, date, software_version, ip_address, notes)
                    VALUES (:chdm_id, :branch_id, :technician_id, :status, :date, :software_version, :ip_address, :notes)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":chdm_id", $this->chdm_id);
            $stmt->bindParam(":branch_id", $this->branch_id);
            $stmt->bindParam(":technician_id", $this->technician_id);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":date", $this->date);
            $stmt->bindParam(":software_version", $this->software_version);
            $stmt->bindParam(":ip_address", $this->ip_address);
            $stmt->bindParam(":notes", $this->notes);

            $success = $stmt->execute();
            return $success;
        }
        public function read() {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT * FROM installation WHERE status = 'success'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;

            
            
        }
        public function read_pending() {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT * FROM installation WHERE status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;

            
        }
        public function update() {
            $conn = DatabaseConnection::getConnection();
            $sql = "UPDATE installation SET status = :status WHERE installation_id = :installation_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam('status', $this-> status);
            $stmt->bindParam('installation_id', $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }
        public function delete() {
            $conn = DatabaseConnection::getConnection();
            $sql = 'DELETE FROM installation WHERE installation_id = :installation_id';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":installation_id", $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }

    }
