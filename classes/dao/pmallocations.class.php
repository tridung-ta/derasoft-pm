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

    public function load(int $user,string $from,string $to,bool $current=false): array {
        // Current read is essential after waiting for the per-user mutex.
        return $this->db->fetchAll('SELECT work_date,hours,start_time,end_time FROM dc_pm_allocations WHERE store_id=? AND user_id=? AND work_date BETWEEN ? AND ? AND deleted_at IS NULL'.($current?' FOR UPDATE':''),'iiss',[$this->storeId,$user,$from,$to]);
    }
    public function overlapping(int $user,string $date,?string $start,?string $end,?int $excludeId=null): bool {
        if($start===null||$end===null)return false;
        return $this->db->fetchOne('SELECT id FROM dc_pm_allocations WHERE store_id=? AND user_id=? AND work_date=? AND deleted_at IS NULL AND (? IS NULL OR id<>?) AND start_time IS NOT NULL AND end_time IS NOT NULL AND ? IS NOT NULL AND ? IS NOT NULL AND start_time < ? AND ? < end_time LIMIT 1 FOR UPDATE','iisiissss',[$this->storeId,$user,$date,$excludeId,$excludeId,$start,$end,$end,$start])!==null;
    }
}
