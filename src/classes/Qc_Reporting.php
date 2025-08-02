 
<?php
class Qc_Reporting extends Model {
    public $qc_id;
    public $chdm_id;
    public $qc_officer_id;
    public $date;
    public $result;
    public $remarks;
    public $test_details;


    public function __construct($qc_id = null, $chdm_id = null, $qc_officer_id = null, $date = null, $result = null, $remarks = null, $test_details = null) {
        $this->qc_id = $qc_id;
        $this->chdm_id = $chdm_id;
        $this->qc_officer_id = $qc_officer_id;
        $this->date = $date;
        $this->result = $result;
        $this->remarks = $remarks;
        $this->test_details = $test_details;
    }

    public function getChdmIdBySerial($serial_no) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT id FROM chdm WHERE serial_no = :serial_no";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':serial_no', $serial_no);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }


  public function create(){
    $conn = DatabaseConnection::getConnection();
    $sql ="INSERT INTO quality_check(chdm_id, qc_officer_id, date, result, remarks, test_details)
           VALUES(:chdm_id, :qc_officer_id, :date, :result, :remarks, :test_details)";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':chdm_id', $this->chdm_id);
    $stmt->bindParam(':qc_officer_id', $this->qc_officer_id);
    $stmt->bindParam(':date', $this->date);
    $stmt->bindParam(':result', $this->result);
    $stmt->bindParam(':remarks', $this->remarks);
    $stmt->bindParam(':test_details', $this->test_details);

    $success = $stmt->execute();

    // If insert successful, update tested_date and state in chdm table
    if ($success) {
        $updateChdm = $conn->prepare("UPDATE chdm SET tested_date = :tested_date, state = :state WHERE id = :chdm_id");
        $updateChdm->bindParam(':tested_date', $this->date);
        $state_lowercase = strtolower($this->result); // Store lowercase result in variable
        $updateChdm->bindParam(':state', $state_lowercase);
        $updateChdm->bindParam(':chdm_id', $this->chdm_id);
        $updateChdm->execute();
    }
    return $success;
  }


  public function read() {
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT qc.*, u.username AS qc_officer_name, c.serial_no, c.state, c.location, c.tested_date
            FROM quality_check qc
            LEFT JOIN users u ON qc.qc_officer_id = u.user_id
            LEFT JOIN chdm c ON qc.chdm_id = c.id";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }


  public function read_failed() {
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT qc.*, u.username AS qc_officer_name, c.serial_no, c.state, c.location, c.tested_date
            FROM quality_check qc
            LEFT JOIN users u ON qc.qc_officer_id = u.user_id
            LEFT JOIN chdm c ON qc.chdm_id = c.id
            WHERE qc.result = 'failed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;

  }

   public function read_passed() {
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT qc.*, u.username AS qc_officer_name, c.serial_no, c.state, c.location, c.tested_date
            FROM quality_check qc
            LEFT JOIN users u ON qc.qc_officer_id = u.user_id
            LEFT JOIN chdm c ON qc.chdm_id = c.id
            WHERE qc.result = 'passed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;
  }



  public function update_result($result = null) {
    $conn = DatabaseConnection::getConnection();
    $sql = "UPDATE quality_check SET result = :result WHERE chdm_id = :chdm_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':result', $result);
    $stmt->bindParam(':chdm_id', $this->chdm_id);
    $success = $stmt->execute();
    return $success;
  }



  public function update_test_details($test_details = null) {
    $conn = DatabaseConnection::getConnection();
    $sql = "UPDATE quality_check SET test_details = :test_details WHERE chdm_id = :chdm_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':test_details', $test_details);
    $stmt->bindParam(':chdm_id', $this->chdm_id);
    $success = $stmt->execute();
    return $success;
  }


  public function update() {
        return false;
    }


  public function delete() {
    $conn = DatabaseConnection::getConnection();
    $sql = "DELETE FROM quality_check WHERE chdm_id = :chdm_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':chdm_id', $this->chdm_id);
    $success = $stmt->execute();
    return $success;
  }
}