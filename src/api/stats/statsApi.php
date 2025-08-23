<?php

class StatsApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            'branch_summary' => ['admin','technician'],
            'branch_trend' => ['admin','technician'],
            'branch_overview' => ['admin','technician'],
            'branch_top' => ['admin','technician'],
            // Newly added stats endpoints
            'installation_summary' => ['admin','technician'],
            'routine_summary' => ['admin','technician'],
            'cluster_summary' => ['admin','technician'],
            'chdm_summary' => ['admin','technician'],
            'user_engagement' => ['admin','technician','Quality_Checker'],
            'qc_summary' => ['admin','technician']
        ]);
    }

    /**
     * GET /api/stats/stats/branch_summary
     * Optional: range_from, range_to (ISO dates)
     */
    public function branch_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'branch_summary')) return ['status'=>'error','message'=>'Unauthorized'];

        [$from,$to] = $this->resolveDateRange($data, 30);
        $conn = DatabaseConnection::getConnection();


        // Total branches
        $totalBranches = (int)$conn->query("SELECT COUNT(*) FROM branch")->fetchColumn();

        // Branches by client (client_name => count)
        $stmt = $conn->query("SELECT c.name AS client_name, COUNT(b.branch_id) AS branch_count FROM branch b JOIN client c ON b.client_id = c.client_id GROUP BY c.name ORDER BY c.name");
        $branchesByClient = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $branchesByClient[$row['client_name']] = (int)$row['branch_count'];
        }

        // Repair counts by client
        $stmt = $conn->prepare("SELECT c.name AS client_name, COUNT(r.repair_id) AS repair_count FROM client c JOIN branch b ON c.client_id = b.client_id LEFT JOIN repair r ON b.branch_id = r.branch_id AND r.start_time BETWEEN :from AND :to GROUP BY c.name ORDER BY c.name");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $repairsByClient = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $repairsByClient[$row['client_name']] = (int)$row['repair_count'];
        }

        // Service counts by client
        $stmt = $conn->prepare("SELECT c.name AS client_name, COUNT(s.service_id) AS service_count FROM client c JOIN branch b ON c.client_id = b.client_id LEFT JOIN service s ON b.branch_id = s.branch_id AND s.service_date BETWEEN :from AND :to GROUP BY c.name ORDER BY c.name");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $servicesByClient = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $servicesByClient[$row['client_name']] = (int)$row['service_count'];
        }

        // Active branches (with at least one repair or service in range)
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT b.branch_id) FROM branch b
            LEFT JOIN repair r ON r.branch_id = b.branch_id AND r.start_time BETWEEN :from AND :to
            LEFT JOIN service s ON s.branch_id = b.branch_id AND s.service_date BETWEEN :from AND :to");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $activeBranches = (int)$stmt->fetchColumn();

        // Repair counts (enhanced): treat 'completed' as closed, detect anomalies, and track completed within range
        $stmt = $conn->prepare("SELECT
            SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_repairs,
            SUM(CASE WHEN status IN ('closed','completed') THEN 1 ELSE 0 END) AS closed_repairs,
            SUM(CASE WHEN status IN ('closed','completed') AND end_time BETWEEN :from AND :to THEN 1 ELSE 0 END) AS completed_in_range,
            SUM(CASE WHEN end_time IS NOT NULL AND end_time < start_time THEN 1 ELSE 0 END) AS negative_duration
            FROM repair
            WHERE start_time <= :to AND (end_time IS NULL OR end_time >= :from)");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $repairRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['open_repairs'=>0,'closed_repairs'=>0,'completed_in_range'=>0,'negative_duration'=>0];

        // Virtual support sessions count (presence of virtual_support_link)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM repair WHERE virtual_support_link IS NOT NULL AND start_time BETWEEN :from AND :to");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $virtualSessions = (int)$stmt->fetchColumn();

        // Service reports count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM service WHERE service_date BETWEEN :from AND :to");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $serviceReports = (int)$stmt->fetchColumn();

        // MTTR (mean time to repair in minutes)
        // Raw (legacy) for reference
        $stmt = $conn->prepare("SELECT AVG(EXTRACT(EPOCH FROM (end_time - start_time))/60)
            FROM repair
            WHERE end_time IS NOT NULL AND end_time BETWEEN :from AND :to");
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $mttrRaw = (float)$stmt->fetchColumn();

        // Filtered MTTR: only closed/completed, non-negative, exclude extreme durations (> 7 days)
        $stmt = $conn->prepare("SELECT AVG(EXTRACT(EPOCH FROM (end_time - start_time))/60)
            FROM repair
            WHERE status IN ('closed','completed')
              AND end_time IS NOT NULL
              AND end_time BETWEEN :from AND :to
              AND end_time >= start_time
              AND EXTRACT(EPOCH FROM (end_time - start_time)) <= :max_seconds");
        $maxSeconds = 7 * 24 * 3600; // 7 days cap
        $stmt->execute([':from'=>$from, ':to'=>$to, ':max_seconds'=>$maxSeconds]);
        $mttrFiltered = (float)$stmt->fetchColumn();
        $mttr = round($mttrFiltered ?: 0, 2);

        return [
            'status'=>'success',
            'range'=>['from'=>$from,'to'=>$to],
            'totals'=>[
                'branches'=>$totalBranches,
                'active_branches'=>$activeBranches,
                'repairs_open'=>(int)$repairRow['open_repairs'],
                'repairs_closed'=>(int)$repairRow['closed_repairs'],
                'repairs_completed_in_range'=>(int)$repairRow['completed_in_range'],
                'repair_anomalies_negative_duration'=>(int)$repairRow['negative_duration'],
                'virtual_sessions'=>$virtualSessions,
                'service_reports'=>$serviceReports,
                'branches_by_client'=>$branchesByClient,
                'repairs_by_client'=>$repairsByClient,
                'services_by_client'=>$servicesByClient
            ],
            'kpis'=>[
                'mttr_minutes'=>$mttr,
                'mttr_minutes_raw'=> round($mttrRaw ?: 0,2)
            ]
        ];
    }

    /** Installation summary stats */
    public function installation_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'installation_summary')) return ['status'=>'error','message'=>'Unauthorized'];

        [$from,$to] = $this->resolveDateRange($data, 30); // default last 30 days by installation date
        $conn = DatabaseConnection::getConnection();
        $out = [];
        try {
            // Core counts
            $stmt = $conn->prepare("SELECT 
                COUNT(*) AS total_installations,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_installations,
                SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) AS completed_installations,
                SUM(CASE WHEN status NOT IN ('pending','success') OR status IS NULL THEN 1 ELSE 0 END) AS other_status
                FROM installation WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['totals'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

                        // Average completion time (hours) for those completed in range.
                        // Cast to timestamp to avoid Postgres error when columns are DATE (DATE - DATE returns integer days).
                        $stmt = $conn->prepare("SELECT AVG(EXTRACT(EPOCH FROM (completion_date::timestamp - date::timestamp))/3600.0) 
                                FROM installation 
                                WHERE completion_date IS NOT NULL AND date IS NOT NULL 
                                    AND completion_date BETWEEN :from AND :to");
                        $stmt->execute([':from'=>$from, ':to'=>$to]);
                        $avgHours = $stmt->fetchColumn();
                        $out['kpis']['avg_completion_hours'] = $avgHours !== null ? round((float)$avgHours,2) : 0.0;

            // Software version distribution (top 10)
            $stmt = $conn->prepare("SELECT COALESCE(software_version,'unknown') AS version, COUNT(*) AS count
                FROM installation WHERE date BETWEEN :from AND :to GROUP BY version ORDER BY count DESC, version ASC LIMIT 10");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['software_versions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Status timeline (daily counts completed)
            $stmt = $conn->prepare("SELECT DATE(completion_date) d, COUNT(*) c FROM installation 
                WHERE completion_date IS NOT NULL AND completion_date BETWEEN :from AND :to GROUP BY d ORDER BY d");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $timelineRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $timelineMap = [];
            foreach($timelineRows as $r){ $timelineMap[$r['d']] = (int)$r['c']; }
            $labels = $this->dateSeries($from,$to);
            $completedPerDay = [];
            foreach($labels as $d){ $completedPerDay[] = $timelineMap[$d] ?? 0; }
            $out['timeline'] = ['labels'=>$labels,'completed'=>$completedPerDay];

            return [
                'status'=>'success',
                'range'=>['from'=>$from,'to'=>$to],
                'totals'=>$out['totals'],
                'kpis'=>$out['kpis'],
                'software_versions'=>$out['software_versions'],
                'timeline'=>$out['timeline']
            ];
        } catch(Exception $e){
            return ['status'=>'error','message'=>'Installation stats error: '.$e->getMessage()];
        }
    }

    /** Routine summary stats */
    public function routine_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'routine_summary')) return ['status'=>'error','message'=>'Unauthorized'];

        [$from,$to] = $this->resolveDateRange($data, 90); // last 90 days default
        $conn = DatabaseConnection::getConnection();
        $out = [];
        try {
            // Routine core counts (assuming routines table with planned_date, status, quarter)
            $stmt = $conn->prepare("SELECT 
                COUNT(*) AS total_routines,
                SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending_routines,
                SUM(CASE WHEN status IN ('completed','done','finished','closed') THEN 1 ELSE 0 END) AS completed_routines
                FROM routines WHERE planned_date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['totals'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Quarter distribution
            $stmt = $conn->prepare("SELECT quarter, COUNT(*) cnt FROM routines WHERE planned_date BETWEEN :from AND :to GROUP BY quarter ORDER BY quarter");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['quarter_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Branch coverage via technician_cluster entries (branches assigned per routine)
            $stmt = $conn->prepare("SELECT routine_id, cluster_branches FROM technician_cluster WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $routineBranchCounts = [];
            $totalAssignedBranches = 0;
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $arr = json_decode($row['cluster_branches'], true) ?: [];
                $cnt = 0;
                // cluster_branches likely array of branch items; count them
                if(is_array($arr)) { $cnt = count($arr); }
                $totalAssignedBranches += $cnt;
                if(!isset($routineBranchCounts[$row['routine_id']])) $routineBranchCounts[$row['routine_id']] = 0;
                $routineBranchCounts[$row['routine_id']] += $cnt;
            }
            $distinctRoutinesWithAssignments = count($routineBranchCounts);
            $out['branch_assignment'] = [
                'total_assigned_branches'=>$totalAssignedBranches,
                'routines_with_assignments'=>$distinctRoutinesWithAssignments,
                'avg_branches_per_assigned_routine' => $distinctRoutinesWithAssignments ? round($totalAssignedBranches / $distinctRoutinesWithAssignments,2) : 0
            ];

            // Derive KPIs
            $kpis = [
                'avg_branches_per_assigned_routine' => $out['branch_assignment']['avg_branches_per_assigned_routine']
            ];
            return [
                'status'=>'success',
                'range'=>['from'=>$from,'to'=>$to],
                'totals'=>$out['totals'],
                'quarter_distribution'=>$out['quarter_distribution'],
                'branch_assignment'=>$out['branch_assignment'],
                'kpis'=>$kpis
            ];
        } catch(Exception $e){
            return ['status'=>'error','message'=>'Routine stats error: '.$e->getMessage()];
        }
    }

    /** Cluster summary stats */
    public function cluster_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'cluster_summary')) return ['status'=>'error','message'=>'Unauthorized'];
        [$from,$to] = $this->resolveDateRange($data, 30);
        $conn = DatabaseConnection::getConnection();
        $out = [];
        try {
            $stmt = $conn->prepare("SELECT cluster_id, quarter, cluster_branches FROM technician_cluster WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $totalClusters = 0; $totalBranches=0; $perQuarter = [];
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                $totalClusters++;
                $branches = json_decode($row['cluster_branches'], true) ?: [];
                $cnt = is_array($branches)? count($branches):0;
                $totalBranches += $cnt;
                $q = $row['quarter'] ?? 'unknown';
                if(!isset($perQuarter[$q])) $perQuarter[$q] = ['clusters'=>0,'branches'=>0];
                $perQuarter[$q]['clusters']++;
                $perQuarter[$q]['branches'] += $cnt;
            }
            $out['totals'] = [
                'clusters'=>$totalClusters,
                'assigned_branches'=>$totalBranches,
                'avg_branches_per_cluster'=>$totalClusters? round($totalBranches/$totalClusters,2):0
            ];
            $formattedQuarter=[]; foreach($perQuarter as $q=>$v){ $v['quarter']=$q; $v['avg_branches_per_cluster'] = $v['clusters']? round($v['branches']/$v['clusters'],2):0; $formattedQuarter[]=$v; }
            $out['quarter_breakdown']=$formattedQuarter;

            // Completion ratio if done_clusters table exists
            try {
                $doneStmt = $conn->prepare("SELECT cluster_id, done_branches FROM done_clusters WHERE done_at BETWEEN :from AND :to");
                $doneStmt->execute([':from'=>$from, ':to'=>$to]);
                $doneBranchesTotal=0; while($r=$doneStmt->fetch(PDO::FETCH_ASSOC)){ $arr=json_decode($r['done_branches'],true)?:[]; $doneBranchesTotal += is_array($arr)? count($arr):0; }
                $out['completion']=['done_branches'=>$doneBranchesTotal,'completion_ratio'=>$totalBranches? round($doneBranchesTotal/$totalBranches,3):0];
            } catch(Exception $inner) {
                $out['completion']=['message'=>'done_clusters table not available'];
            }

            // KPIs
            $kpis = [
                'avg_branches_per_cluster' => $out['totals']['avg_branches_per_cluster'],
                'completion_ratio' => $out['completion']['completion_ratio'] ?? null
            ];
            return [
                'status'=>'success',
                'range'=>['from'=>$from,'to'=>$to],
                'totals'=>$out['totals'],
                'quarter_breakdown'=>$out['quarter_breakdown'],
                'completion'=>$out['completion'],
                'kpis'=>$kpis
            ];
        } catch(Exception $e){
            return ['status'=>'error','message'=>'Cluster stats error: '.$e->getMessage()];
        }
    }

    /** CHDM devices summary */
    public function chdm_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'chdm_summary')) return ['status'=>'error','message'=>'Unauthorized'];
        [$from,$to] = $this->resolveDateRange($data, 30); // tested_date range
        $conn = DatabaseConnection::getConnection();
        $out = [];
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM chdm");
            $total = (int)$stmt->fetchColumn();
            $row = $conn->query("SELECT 
                SUM(CASE WHEN state='passed' THEN 1 ELSE 0 END) passed,
                SUM(CASE WHEN state='failed' THEN 1 ELSE 0 END) failed
                FROM chdm")->fetch(PDO::FETCH_ASSOC);
            $out['totals'] = [
                'devices_total'=>$total,
                'passed'=>(int)$row['passed'],
                'failed'=>(int)$row['failed'],
                'pass_rate'=>$total? round($row['passed']/$total,3):0
            ];
            // Assigned vs unassigned (only passed)
            $row2 = $conn->query("SELECT 
                SUM(CASE WHEN state='passed' AND branch_id IS NOT NULL THEN 1 ELSE 0 END) assigned_passed,
                SUM(CASE WHEN state='passed' AND branch_id IS NULL THEN 1 ELSE 0 END) unassigned_passed
                FROM chdm")->fetch(PDO::FETCH_ASSOC);
            $out['assignment'] = [
                'assigned_passed'=>(int)$row2['assigned_passed'],
                'unassigned_passed'=>(int)$row2['unassigned_passed']
            ];
            // Tested in range
            $stmt = $conn->prepare("SELECT COUNT(*) FROM chdm WHERE tested_date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['tested_in_range'] = (int)$stmt->fetchColumn();
            $kpis = [
                'pass_rate' => $out['totals']['pass_rate']
            ];
            return [
                'status'=>'success',
                'range'=>['from'=>$from,'to'=>$to],
                'totals'=>$out['totals'],
                'assignment'=>$out['assignment'],
                'tested_in_range'=>$out['tested_in_range'],
                'kpis'=>$kpis
            ];
        } catch(Exception $e){
            return ['status'=>'error','message'=>'CHDM stats error: '.$e->getMessage()];
        }
    }

    /** QC reporting summary */
    public function qc_summary($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'qc_summary')) return ['status'=>'error','message'=>'Unauthorized'];
        [$from,$to] = $this->resolveDateRange($data, 30); // QC date range
        $conn = DatabaseConnection::getConnection();
        $out = [];
        try {
            $stmt = $conn->prepare("SELECT 
                COUNT(*) total_checks,
                SUM(CASE WHEN result='passed' THEN 1 ELSE 0 END) AS passed_checks,
                SUM(CASE WHEN result='failed' THEN 1 ELSE 0 END) AS failed_checks
                FROM quality_check WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $total = (int)($row['total_checks'] ?? 0);
            $passed = (int)($row['passed_checks'] ?? 0);
            $failed = (int)($row['failed_checks'] ?? 0);
            $out['totals'] = [
                'total_checks'=>$total,
                'passed_checks'=>$passed,
                'failed_checks'=>$failed,
                'pass_rate'=>$total? round($passed/$total,3):0
            ];
            // Distinct devices tested & most recent test date
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT chdm_id) FROM quality_check WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['distinct_devices_in_range'] = (int)$stmt->fetchColumn();
            $stmt = $conn->prepare("SELECT MAX(date) FROM quality_check WHERE date BETWEEN :from AND :to");
            $stmt->execute([':from'=>$from, ':to'=>$to]);
            $out['most_recent_test_date'] = $stmt->fetchColumn();
            $kpis = [
                'pass_rate' => $out['totals']['pass_rate']
            ];
            return [
                'status'=>'success',
                'range'=>['from'=>$from,'to'=>$to],
                'totals'=>$out['totals'],
                'distinct_devices_in_range'=>$out['distinct_devices_in_range'],
                'most_recent_test_date'=>$out['most_recent_test_date'],
                'kpis'=>$kpis
            ];
        } catch(Exception $e){
            return ['status'=>'error','message'=>'QC stats error: '.$e->getMessage()];
        }
    }

    /** User engagement stats: top users by services, repairs, done cluster completions, QC reports */
    public function user_engagement($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'user_engagement')) return ['status'=>'error','message'=>'Unauthorized'];

        [$from,$to] = $this->resolveDateRange($data, 30);
        $limit = min(max((int)($data['limit'] ?? 10),1),100);
        $conn = DatabaseConnection::getConnection();

    // Top users by services (service.user_id) - only users with role 'technician'
    $stmt = $conn->prepare("SELECT u.user_id, u.username, COUNT(s.service_id) AS services_count FROM users u JOIN roles role ON u.role_id = role.role_id AND role.role_name = 'technician' LEFT JOIN service s ON s.user_id = u.user_id AND s.service_date BETWEEN :from AND :to GROUP BY u.user_id, u.username ORDER BY services_count DESC, u.username LIMIT :limit");
    $stmt->bindParam(':from', $from);
    $stmt->bindParam(':to', $to);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top users by repairs (repair.technician_id) - only users with role 'technician'
    $stmt = $conn->prepare("SELECT u.user_id, u.username, COUNT(r.repair_id) AS repairs_count FROM users u JOIN roles role ON u.role_id = role.role_id AND role.role_name = 'technician' LEFT JOIN repair r ON r.technician_id = u.user_id AND r.start_time BETWEEN :from AND :to GROUP BY u.user_id, u.username ORDER BY repairs_count DESC, u.username LIMIT :limit");
    $stmt->bindParam(':from', $from);
    $stmt->bindParam(':to', $to);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $repairs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Top users by done_clusters completions (done_clusters.user_id, count array length)
        // We count number of done_branches entries per user in range
        try {
            // Only technicians (done_clusters are recorded per technician user)
            $doneStmt = $conn->prepare("SELECT u.user_id, u.username, SUM(json_array_length(dc.done_branches::json)) AS completed_branches FROM users u JOIN roles role ON u.role_id = role.role_id AND role.role_name = 'technician' LEFT JOIN done_clusters dc ON dc.user_id = u.user_id AND dc.done_at BETWEEN :from AND :to GROUP BY u.user_id, u.username ORDER BY completed_branches DESC NULLS LAST, u.username LIMIT :limit");
            $doneStmt->bindParam(':from', $from);
            $doneStmt->bindParam(':to', $to);
            $doneStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $doneStmt->execute();
            $doneClusters = $doneStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(Exception $e) {
            // If done_clusters or json_array_length not available, return empty
            $doneClusters = [];
        }

    // Top QC officers by quality_check entries - only users with role 'Quality_Checker'
    $stmt = $conn->prepare("SELECT u.user_id, u.username, COUNT(q.qc_id) AS qc_count FROM users u JOIN roles role ON u.role_id = role.role_id AND role.role_name = 'Quality_Checker' LEFT JOIN quality_check q ON q.qc_officer_id = u.user_id AND q.date BETWEEN :from AND :to GROUP BY u.user_id, u.username ORDER BY qc_count DESC, u.username LIMIT :limit");
    $stmt->bindParam(':from', $from);
    $stmt->bindParam(':to', $to);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $qc = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'status'=>'success',
            'range'=>['from'=>$from,'to'=>$to],
            'limit'=>$limit,
            'by_services'=>$services,
            'by_repairs'=>$repairs,
            'by_done_clusters'=>$doneClusters,
            'by_qc'=>$qc
        ];
    }

    /** Trend of repairs created / closed per day */
    public function branch_trend($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'branch_trend')) return ['status'=>'error','message'=>'Unauthorized'];

        [$from,$to] = $this->resolveDateRange($data, 14);
        $conn = DatabaseConnection::getConnection();

        $createdMap = $this->dateCountMap($conn, "SELECT DATE(start_time) d, COUNT(*) c FROM repair WHERE start_time BETWEEN :from AND :to GROUP BY d", $from, $to);
        $closedMap = $this->dateCountMap($conn, "SELECT DATE(end_time) d, COUNT(*) c FROM repair WHERE end_time IS NOT NULL AND end_time BETWEEN :from AND :to GROUP BY d", $from, $to);
        $virtualMap = $this->dateCountMap($conn, "SELECT DATE(start_time) d, COUNT(*) c FROM repair WHERE virtual_support_link IS NOT NULL AND start_time BETWEEN :from AND :to GROUP BY d", $from, $to);

        $labels = $this->dateSeries($from,$to);
        $created=[]; $closed=[]; $virtual=[];
        foreach($labels as $d){
            $created[] = $createdMap[$d] ?? 0;
            $closed[] = $closedMap[$d] ?? 0;
            $virtual[] = $virtualMap[$d] ?? 0;
        }
        return [
            'status'=>'success',
            'range'=>['from'=>$from,'to'=>$to],
            'group'=>'day',
            'labels'=>$labels,
            'repairs_created'=>$created,
            'repairs_closed'=>$closed,
            'virtual_support_sessions'=>$virtual
        ];
    }

    /** Overview for specific branch */
    public function branch_overview($data){
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'branch_overview')) return ['status'=>'error','message'=>'Unauthorized'];
        if(empty($data['branch_id'])) return ['status'=>'error','message'=>'branch_id required'];
        $branchId = (int)$data['branch_id'];

        [$from,$to] = $this->resolveDateRange($data, 30);
        $conn = DatabaseConnection::getConnection();

        $stmt = $conn->prepare("SELECT * FROM branch WHERE branch_id=:id");
        $stmt->execute([':id'=>$branchId]);
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$branch) return ['status'=>'error','message'=>'Branch not found'];

        // Repairs stats
        $stmt = $conn->prepare("SELECT
            SUM(CASE WHEN status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_cnt,
            SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) AS in_progress_cnt,
            SUM(CASE WHEN status='closed' THEN 1 ELSE 0 END) AS closed_cnt,
            SUM(CASE WHEN status IN ('pending','in_progress','closed') THEN 1 ELSE 0 END) AS total_cnt
            FROM repair WHERE branch_id=:bid");
        $stmt->execute([':bid'=>$branchId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Aging buckets (open only)
        $stmt = $conn->prepare("SELECT
            SUM(CASE WHEN status IN ('pending','in_progress') AND NOW()-start_time < interval '24 hours' THEN 1 ELSE 0 END) AS lt24,
            SUM(CASE WHEN status IN ('pending','in_progress') AND NOW()-start_time BETWEEN interval '24 hours' AND interval '48 hours' THEN 1 ELSE 0 END) AS btw24_48,
            SUM(CASE WHEN status IN ('pending','in_progress') AND NOW()-start_time > interval '48 hours' THEN 1 ELSE 0 END) AS gt48
            FROM repair WHERE branch_id=:bid");
        $stmt->execute([':bid'=>$branchId]);
        $aging = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        // Closed in range for MTTR
        $stmt = $conn->prepare("SELECT AVG(EXTRACT(EPOCH FROM (end_time - start_time))/3600) FROM repair WHERE branch_id=:bid AND end_time BETWEEN :from AND :to");
        $stmt->execute([':bid'=>$branchId, ':from'=>$from, ':to'=>$to]);
        $mttrHours = round((float)$stmt->fetchColumn(),2);

        // Service reports this quarter (approx by date range) & last 30d
        $stmt = $conn->prepare("SELECT COUNT(*) FROM service WHERE branch_id=:bid AND service_date BETWEEN :from AND :to");
        $stmt->execute([':bid'=>$branchId, ':from'=>$from, ':to'=>$to]);
        $serviceCount = (int)$stmt->fetchColumn();

        // Virtual sessions last range
        $stmt = $conn->prepare("SELECT COUNT(*) FROM repair WHERE branch_id=:bid AND virtual_support_link IS NOT NULL AND start_time BETWEEN :from AND :to");
        $stmt->execute([':bid'=>$branchId, ':from'=>$from, ':to'=>$to]);
        $virtualSessions = (int)$stmt->fetchColumn();

        return [
            'status'=>'success',
            'branch_id'=>$branchId,
            'branch_name'=>$branch['name'],
            'repairs'=>[
                'open'=>(int)$r['open_cnt'],
                'in_progress'=>(int)$r['in_progress_cnt'],
                'closed'=>(int)$r['closed_cnt'],
                'aging'=>[
                    'lt24h'=>(int)$aging['lt24'],
                    '24to48h'=>(int)$aging['btw24_48'],
                    'gt48h'=>(int)$aging['gt48']
                ],
                'mttr_hours'=>$mttrHours
            ],
            'service_reports'=>[
                'in_range'=>$serviceCount
            ],
            'virtual_support'=>[
                'sessions_in_range'=>$virtualSessions
            ]
        ];
    }

    /** Top branches by metric (open_repairs default) */
    public function branch_top($data){
        $user = $this->getAuthenticatedUser();
        if(!$user) return ['status'=>'error','message'=>'Invalid authentication token'];
        if(!$this->checkRoles($user['role_name'], 'branch_top')) return ['status'=>'error','message'=>'Unauthorized'];

        $metric = $data['metric'] ?? 'open_repairs';
        $limit = min(max((int)($data['limit'] ?? 10),1),50);
        $conn = DatabaseConnection::getConnection();

        $sql = "SELECT b.branch_id, b.name,
            SUM(CASE WHEN r.status IN ('pending','in_progress') THEN 1 ELSE 0 END) AS open_repairs,
            SUM(CASE WHEN r.status='closed' THEN 1 ELSE 0 END) AS closed_repairs,
            AVG(CASE WHEN r.end_time IS NOT NULL THEN EXTRACT(EPOCH FROM (r.end_time - r.start_time))/3600 END) AS mttr_hours
            FROM branch b
            LEFT JOIN repair r ON r.branch_id = b.branch_id
            GROUP BY b.branch_id, b.name";
        $rows = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        // Sort in PHP based on requested metric if not a direct column order
        usort($rows, function($a,$b) use ($metric){
            return ($b[$metric] ?? 0) <=> ($a[$metric] ?? 0);
        });
        $rows = array_slice($rows,0,$limit);
        return [
            'status'=>'success',
            'metric'=>$metric,
            'items'=>$rows
        ];
    }

    /* ----------------- Helpers ------------------ */
    private function resolveDateRange($data, $defaultDays){
        $to = $data['range_to'] ?? date('Y-m-d');
        $from = $data['range_from'] ?? date('Y-m-d', strtotime('-'.$defaultDays.' days', strtotime($to)));
        return [$from, $to];
    }
    private function dateCountMap($conn,$sql,$from,$to){
        $stmt = $conn->prepare($sql);
        $stmt->execute([':from'=>$from, ':to'=>$to]);
        $out=[]; foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){ $out[$row['d']] = (int)$row['c']; } return $out;
    }
    private function dateSeries($from,$to){
        $series=[]; $cur=strtotime($from); $end=strtotime($to); while($cur <= $end){ $series[] = date('Y-m-d',$cur); $cur = strtotime('+1 day',$cur);} return $series;
    }
}
