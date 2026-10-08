<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

class PmAllocations {
    public PmDb $db;
    public function __construct($database, private int $storeId) {$this->db=new PmDb($database);}

    public function find(int $id, bool $lock=false): ?array {
        return $this->db->fetchOne('SELECT * FROM dc_pm_allocations WHERE store_id=? AND id=? AND deleted_at IS NULL'.($lock?' FOR UPDATE':''),'ii',[$this->storeId,$id]);
    }

    public function lockUsers(array $keys): void {
        $users=[];foreach($keys as [$user,$day])$users[(int)$user]=true;
        $ids=array_keys($users);sort($ids,SORT_NUMERIC);
        foreach($ids as $user)$this->lockKey($user,'1000-01-01');
        usort($keys,fn($a,$b)=>($a[0]<=>$b[0])?:strcmp($a[1],$b[1]));
        foreach($keys as [$user,$day])$this->lockKey((int)$user,$day);
    }
    private function lockKey(int $user,string $day): void {
        $this->db->execute('INSERT IGNORE INTO dc_pm_allocation_locks(store_id,user_id,work_date) VALUES(?,?,?)','iis',[$this->storeId,$user,$day]);
        $this->db->fetchOne('SELECT user_id FROM dc_pm_allocation_locks WHERE store_id=? AND user_id=? AND work_date=? FOR UPDATE','iis',[$this->storeId,$user,$day]);
    }


    public function taskEstimateWarnings(array $taskIds,?int $managerId=null): array {
        $taskIds=array_values(array_unique(array_map('intval',$taskIds)));
        if(!$taskIds)return [];
        $marks=implode(',',array_fill(0,count($taskIds),'?'));
        $types='i'.str_repeat('i',count($taskIds));$params=[$this->storeId,...$taskIds];
        $scope='';if($managerId!==null){$scope=' AND p.manager_id=?';$types.='i';$params[]=$managerId;}
        // Aggregate timesheets directly: multiple allocations must not multiply hours.
        // MySQL DECIMAL SUM/HAVING/subtraction preserve precision; no PHP float conversion.
        return $this->db->fetchAll('SELECT t.id task_id,t.project_id,t.name task_name,p.name project_name,t.estimated_hours,
            SUM(s.hours) actual_hours,SUM(s.hours)-t.estimated_hours excess_hours,
            (t.deleted_at IS NOT NULL OR p.deleted_at IS NOT NULL) historical
            FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id
            JOIN dc_pm_timesheets s ON s.store_id=t.store_id AND s.project_id=t.project_id AND s.task_id=t.id AND s.deleted_at IS NULL
            WHERE t.store_id=? AND t.id IN ('.$marks.') AND t.estimated_hours>0'.$scope.'
            GROUP BY t.id,t.project_id,t.name,p.name,t.estimated_hours,t.deleted_at,p.deleted_at
            HAVING SUM(s.hours)>t.estimated_hours ORDER BY t.id',$types,$params);
    }

    public function load(int $user,string $from,string $to,bool $current=false): array {
        // Current read is essential after waiting for the per-user mutex.
        return $this->db->fetchAll('SELECT work_date,hours,start_time,end_time FROM dc_pm_allocations WHERE store_id=? AND user_id=? AND work_date BETWEEN ? AND ? AND deleted_at IS NULL'.($current?' FOR UPDATE':''),'iiss',[$this->storeId,$user,$from,$to]);
    }
    public function overlapping(int $user,string $date,?string $start,?string $end,?int $excludeId=null): bool {
        if($start===null||$end===null)return false;
        return $this->db->fetchOne('SELECT id FROM dc_pm_allocations WHERE store_id=? AND user_id=? AND work_date=? AND deleted_at IS NULL AND (? IS NULL OR id<>?) AND start_time IS NOT NULL AND end_time IS NOT NULL AND ? IS NOT NULL AND ? IS NOT NULL AND start_time < ? AND ? < end_time LIMIT 1 FOR UPDATE','iisiissss',[$this->storeId,$user,$date,$excludeId,$excludeId,$start,$end,$end,$start])!==null;
    }
}
