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
    return $success;
  }
  public function read() {
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT * FROM quality_check WHERE result = 'passed'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $result;

  }
  public function read_failed() {
    $conn = DatabaseConnection::getConnection();
    $sql = "SELECT * FROM quality_check WHERE result = 'failed'";
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