<?php

class BackupAssignment extends Model {
    public $assignment_id;
    public $backup_id;
    public $repair_id;
    public $location;
    public $sent_date;
    public $received_date;

    // Joined data
    public $backup_machine;
    public $repair_details;
    public $branch_details;

    public function __construct($assignment_id = null, $backup_id = null, $repair_id = null, $location = null, $sent_date = null, $received_date = null) {
        $this->assignment_id = $assignment_id;
        $this->backup_id = $backup_id;
        $this->repair_id = $repair_id;
        $this->location = $location;
        $this->sent_date = $sent_date;
        $this->received_date = $received_date;
    }

    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO backup_assignment (backup_id, repair_id, location, sent_date, received_date) VALUES (:backup_id, :repair_id, :location, :sent_date, :received_date)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $this->backup_id);
        $stmt->bindParam(':repair_id', $this->repair_id);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':sent_date', $this->sent_date);
        $stmt->bindParam(':received_date', $this->received_date);
        $success = $stmt->execute();

        if ($success) {
            $this->assignment_id = $conn->lastInsertId();
        }

        return $success;
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                WHERE ba.assignment_id = :assignment_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':assignment_id', $this->assignment_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return false; // Record not found
        }

        $this->backup_id = $result['backup_id'];
        $this->repair_id = $result['repair_id'];
        $this->location = $result['location'];
        $this->sent_date = $result['sent_date'];
        $this->received_date = $result['received_date'];

        // Populate joined data
        $this->backup_machine = [
            'backup_id' => $result['backup_id'],
            'serial_no' => $result['serial_no'],
            'model' => $result['model'],
            'status' => $result['backup_status']
        ];

        $this->repair_details = [
            'repair_id' => $result['repair_id'],
            'device_type' => $result['device_type'],
            'device_id' => $result['device_id'],
            'branch_id' => $result['branch_id'],
            'technician_id' => $result['technician_id'],
            'start_time' => $result['start_time'],
            'end_time' => $result['end_time'],
            'status' => $result['repair_status'],
            'summary' => $result['summary'],
            'virtual_support_link' => $result['virtual_support_link'],
            'backup_sent' => $result['backup_sent'],
            'visit_required' => $result['visit_required'],
            'technician_name' => $result['technician_name']
        ];

        $this->branch_details = [
            'branch_id' => $result['branch_id'],
            'name' => $result['branch_name']
        ];

        return true;
    }

    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE backup_assignment SET backup_id = :backup_id, repair_id = :repair_id, location = :location, sent_date = :sent_date, received_date = :received_date WHERE assignment_id = :assignment_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':assignment_id', $this->assignment_id);
        $stmt->bindParam(':backup_id', $this->backup_id);
        $stmt->bindParam(':repair_id', $this->repair_id);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':sent_date', $this->sent_date);
        $stmt->bindParam(':received_date', $this->received_date);

        return $stmt->execute();
    }

    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM backup_assignment WHERE assignment_id = :assignment_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':assignment_id', $this->assignment_id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public static function getAll() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name,
                       CASE WHEN ba.location = 0 THEN 'Office' ELSE b2.name END as location_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                LEFT JOIN branch b2 ON ba.location = b2.branch_id
                ORDER BY ba.assignment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByBackupId($backup_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name,
                       CASE WHEN ba.location = 0 THEN 'Office' ELSE b2.name END as location_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                LEFT JOIN branch b2 ON ba.location = b2.branch_id
                WHERE ba.backup_id = :backup_id
                ORDER BY ba.assignment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $backup_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByRepairId($repair_id) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name,
                       CASE WHEN ba.location = 0 THEN 'Office' ELSE b2.name END as location_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                LEFT JOIN branch b2 ON ba.location = b2.branch_id
                WHERE ba.repair_id = :repair_id
                ORDER BY ba.assignment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':repair_id', $repair_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getByLocation($location) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name,
                       CASE WHEN ba.location = 0 THEN 'Office' ELSE b2.name END as location_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                LEFT JOIN branch b2 ON ba.location = b2.branch_id
                WHERE ba.location = :location
                ORDER BY ba.assignment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':location', $location);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getActiveAssignments() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT ba.*,
                       bm.serial_no, bm.model, bm.status as backup_status,
                       r.device_type, r.device_id, r.branch_id, r.technician_id, r.start_time, r.end_time, r.status as repair_status, r.summary, r.virtual_support_link, r.backup_sent, r.visit_required,
                       u.username as technician_name,
                       b.name as branch_name,
                       CASE WHEN ba.location = 0 THEN 'Office' ELSE b2.name END as location_name
                FROM backup_assignment ba
                LEFT JOIN backup_machine bm ON ba.backup_id = bm.backup_id
                LEFT JOIN repair r ON ba.repair_id = r.repair_id
                LEFT JOIN users u ON r.technician_id = u.user_id
                LEFT JOIN branch b ON r.branch_id = b.branch_id
                LEFT JOIN branch b2 ON ba.location = b2.branch_id
                WHERE ba.received_date IS NULL
                ORDER BY ba.assignment_id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
