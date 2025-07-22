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
        
        public $serial_no;

        public function __construct($installation_id = null, $chdm_id = null, $branch_id = null, $technician_id = null, $status = null, $date = null, $completion_date = null, $software_version = null, $ip_address = null, $notes = null, $serial_no = null){
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
            $this->serial_no = $serial_no;
        }

        public function create(){
            $conn = DatabaseConnection::getConnection();
            $sql = "INSERT INTO installation (chdm_id, branch_id, technician_id, status, date, software_version, ip_address, notes, serial_no)
                    VALUES (:chdm_id, :branch_id, :technician_id, :status, :date, :software_version, :ip_address, :notes, :serial_no)";
            $stmt = $conn->prepare($sql);

            $stmt->bindParam(":chdm_id", $this->chdm_id);
            $stmt->bindParam(":branch_id", $this->branch_id);
            $stmt->bindParam(":technician_id", $this->technician_id);
            $stmt->bindParam(":status", $this->status);
            $stmt->bindParam(":date", $this->date);
            $stmt->bindParam(":software_version", $this->software_version);
            $stmt->bindParam(":ip_address", $this->ip_address);
            $stmt->bindParam(":notes", $this->notes);
            $stmt->bindParam(":serial_no", $this->serial_no);

            $success = $stmt->execute();
            return $success;
        }
        public function read() {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT 
                        i.installation_id,
                        i.chdm_id,
                        i.branch_id,
                        i.technician_id,
                        i.status,
                        i.date,
                        i.completion_date,
                        i.software_version,
                        i.ip_address,
                        i.notes,
                        i.serial_no,
                        u.username as technician_name,
                        u.email as technician_email,
                        u.phone as technician_phone
                    FROM installation i
                    LEFT JOIN users u ON i.technician_id = u.user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }

        
        public function read_with_technician() {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT 
                        i.installation_id,
                        i.chdm_id,
                        i.branch_id,
                        i.technician_id,
                        i.status,
                        i.date,
                        i.completion_date,
                        i.software_version,
                        i.ip_address,
                        i.notes,
                        i.serial_no,
                        u.username as technician_name,
                        u.email as technician_email,
                        u.phone as technician_phone
                    FROM installation i
                    LEFT JOIN users u ON i.technician_id = u.user_id
                    WHERE i.status = 'success'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
        public function read_pending() {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT 
                        i.installation_id,
                        i.chdm_id,
                        i.branch_id,
                        i.technician_id,
                        i.status,
                        i.date,
                        i.completion_date,
                        i.software_version,
                        i.ip_address,
                        i.notes,
                        i.serial_no,
                        u.username as technician_name,
                        u.email as technician_email,
                        u.phone as technician_phone
                    FROM installation i
                    LEFT JOIN users u ON i.technician_id = u.user_id
                    WHERE i.status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result;

        }
         public function update_status($status) {
            $conn = DatabaseConnection::getConnection();
            $sql = "UPDATE installation SET status = :status WHERE installation_id = :installation_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':installation_id', $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }

        public function software_version($software_version) {
            $conn = DatabaseConnection::getConnection();
            $sql = "UPDATE installation SET software_version = :software_version WHERE installation_id = :installation_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':software_version', $software_version);
            $stmt->bindParam(':installation_id', $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }

        public function completion_date($completion_date) {
            $conn = DatabaseConnection::getConnection();
            $sql = "UPDATE installation SET completion_date = :completion_date WHERE installation_id = :installation_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':completion_date', $completion_date);
            $stmt->bindParam(':installation_id', $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }

        public function notes($notes) {
            $conn = DatabaseConnection::getConnection();
            $sql = "UPDATE installation SET notes = :notes WHERE installation_id = :installation_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':installation_id', $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }
       public function update() {
        // Implement update logic here
        // Example: Update chdm in database by $this->id
        return true;
    }
        public function delete() {
            $conn = DatabaseConnection::getConnection();
            $sql = 'DELETE FROM installation WHERE installation_id = :installation_id';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":installation_id", $this->installation_id);
            $success = $stmt->execute();
            return $success;
        }
      public function update_all() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE installation SET chdm_id = :chdm_id, branch_id = :branch_id, technician_id = :technician_id, status = :status, date = :date, completion_date = :completion_date, software_version = :software_version, ip_address = :ip_address, notes = :notes, serial_no = :serial_no WHERE installation_id = :installation_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":chdm_id", $this->chdm_id);
        $stmt->bindParam(":branch_id", $this->branch_id);
        $stmt->bindParam(":technician_id", $this->technician_id);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":date", $this->date);
        $stmt->bindParam(":completion_date", $this->completion_date);
        $stmt->bindParam(":software_version", $this->software_version);
        $stmt->bindParam(":ip_address", $this->ip_address);
        $stmt->bindParam(":notes", $this->notes);
        $stmt->bindParam(":serial_no", $this->serial_no);
        $stmt->bindParam(":installation_id", $this->installation_id);
        $success = $stmt->execute();
        return $success;
      }
    }
