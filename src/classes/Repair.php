<?php
class Repair extends Model {
    public $repair_id;
    public $device_type;
    public $device_id;
    public $branch_id;
    public $technician_id;      
    public $start_time;
    public $end_time;
    public $status;
    public $summary;
    public $virtual_support_link;
    public $backup_sent;
    public $visit_required;
    public $technician_name; // Added to store technician name from JOIN
    public $branch_name; // Added to store branch name from JOIN

    public function __construct($repair_id = null, $device_type = null, $device_id = null, $branch_id = null, $technician_id = null, $start_time = null, $end_time = null, $status = null, $summary = null, $virtual_support_link = null, $backup_sent = null, $visit_required = null) {
        $this->repair_id = $repair_id;
        $this->device_type = $device_type;
        $this->device_id = $device_id;
        $this->branch_id = $branch_id;
        $this->technician_id = $technician_id;
        $this->start_time = $start_time;
        $this->end_time = $end_time;
        $this->status = $status;
        $this->summary = $summary;
        $this->virtual_support_link = $virtual_support_link;
        $this->backup_sent = $backup_sent;
        $this->visit_required = $visit_required;
    }

public function create(){
$conn =DatabaseConnection :: getConnection();
$status = $this->status ?? 'pending';
$sql ="INSERT INTO repair(device_type,device_id,branch_id,technician_id,start_time,status,virtual_support_link) VALUES (:device_type, :device_id, :branch_id, :technician_id, :start_time, :status, :virtual_support_link)";
$stmt = $conn->prepare($sql);
$stmt->bindParam("device_type", $this->device_type);
$stmt->bindParam("device_id", $this->device_id);
$stmt->bindParam("branch_id", $this->branch_id);
$stmt->bindParam("technician_id", $this->technician_id);
$stmt->bindParam("start_time", $this->start_time);
$stmt->bindParam("status", $status);
$stmt->bindParam("virtual_support_link", $this->virtual_support_link);
$success = $stmt->execute();
return $success;

}

public function read(){
  $conn = DatabaseConnection::getConnection();
  $sql = "SELECT r.*, u.username as technician_name, b.name as branch_name 
          FROM repair r 
          LEFT JOIN users u ON r.technician_id = u.user_id 
          LEFT JOIN branch b ON r.branch_id = b.branch_id 
          ORDER BY r.repair_id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  return $results;
}

public function readAll(){
  $conn = DatabaseConnection::getConnection();
  $sql = "SELECT r.*, u.username as technician_name, b.name as branch_name 
          FROM repair r 
          LEFT JOIN users u ON r.technician_id = u.user_id 
          LEFT JOIN branch b ON r.branch_id = b.branch_id 
          ORDER BY r.repair_id DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute();
  $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  return $results;
}

public function update(){
$conn = DatabaseConnection::getConnection();

$sql =  'UPDATE repair SET status = :status, end_time = :end_time, summary = :summary, virtual_support_link = :virtual_support_link, backup_sent = :backup_sent, visit_required = :visit_required WHERE repair_id = :repair_id';
$stmt = $conn->prepare($sql);
$stmt->bindParam(':repair_id', $this->repair_id);
$stmt->bindParam(':status', $this->status);
$stmt->bindParam(':end_time', $this->end_time);
$stmt->bindParam(':summary', $this->summary);
$stmt->bindParam(':virtual_support_link', $this->virtual_support_link);
$stmt->bindParam(':backup_sent', $this->backup_sent);
$stmt->bindParam(':visit_required', $this->visit_required);
$success = $stmt->execute();
return $success;

}

public function updateAll(){
    $conn = DatabaseConnection::getConnection();
    
    $sql = 'UPDATE repair SET 
            device_type = :device_type,
            device_id = :device_id,
            branch_id = :branch_id,
            technician_id = :technician_id,
            start_time = :start_time,
            end_time = :end_time,
            status = :status,
            summary = :summary,
            virtual_support_link = :virtual_support_link,
            backup_sent = :backup_sent,
            visit_required = :visit_required
            WHERE repair_id = :repair_id';
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':repair_id', $this->repair_id);
    $stmt->bindParam(':device_type', $this->device_type);
    $stmt->bindParam(':device_id', $this->device_id);
    $stmt->bindParam(':branch_id', $this->branch_id);
    $stmt->bindParam(':technician_id', $this->technician_id);
    $stmt->bindParam(':start_time', $this->start_time);
    $stmt->bindParam(':end_time', $this->end_time);
    $stmt->bindParam(':status', $this->status);
    $stmt->bindParam(':summary', $this->summary);
    $stmt->bindParam(':virtual_support_link', $this->virtual_support_link);
    $stmt->bindParam(':backup_sent', $this->backup_sent);
    $stmt->bindParam(':visit_required', $this->visit_required);
    
    $success = $stmt->execute();
    return $success;
}

public function delete(){
$conn = DatabaseConnection::getConnection(); 
$sql = 'DELETE FROM repair WHERE repair_id = :repair_id';
$stmt = $conn->prepare($sql);
$stmt->bindParam(':repair_id', $this->repair_id);
$success = $stmt->execute();    
return $success;
}
}







