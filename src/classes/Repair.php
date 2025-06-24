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
$sql ="INSERT INTO repair(device_type,device_id,branch_id,technician_id,start_time,status) VALUES (:device_type, :device_id, :branch_id, :technician_id, :start_time, :status)";
$stmt = $conn->prepare($sql);
$stmt->bindParam("device_type", $this->device_type);
$stmt->bindParam("device_id", $this->device_id);
$stmt->bindParam("branch_id", $this->branch_id);
$stmt->bindParam("technician_id", $this->technician_id);
$stmt->bindParam("start_time", $this->start_time);
$stmt->bindParam("status", $status);
$success = $stmt->execute();
return $success;

}

public function read(){
  $conn =DatabaseConnection :: getConnection();
  $sql ="SELECT * FROM repair WHERE repair_id =:repair_id;";
  $stmt = $conn->prepare($sql);
  $stmt->bindParam("repair_id", $this->repair_id);
  $stmt->execute();
 $result = $stmt->fetch(PDO::FETCH_ASSOC);

$this->device_type = $result['device_type'];
$this->device_id = $result['device_id'];
$this->branch_id = $result['branch_id'];
$this->technician_id = $result['technician_id'];
$this->start_time = $result['start_time'];
$this->end_time = $result['end_time'];
$this->status = $result['status'];
$this->summary = $result['summary'];
$this->virtual_support_link = $result['virtual_support_link'];
$this->backup_sent = $result['backup_sent'];
$this->visit_required = $result['visit_required'];
return $result['repair_id'] !== null; 

}
public function update(){
$conn = DatabaseConnection::getConnection();
// Fixed SQL syntax: removed 'FROM'
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

public function delete(){
$conn = DatabaseConnection::getConnection(); 
$sql = 'DELETE FROM repair WHERE repair_id = :repair_id';
$stmt = $conn->prepare($sql);
$stmt->bindParam(':repair_id', $this->repair_id);
$success = $stmt->execute();    
return $success;
}
}







