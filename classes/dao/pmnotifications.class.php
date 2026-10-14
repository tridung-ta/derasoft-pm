<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/dao/pmnotificationinfo.class.php');
/** All reads revalidate current eligibility; history is never deleted. */
class PmNotifications {
    public function __construct(protected PmDb $db,protected int $storeId,protected array $userIds=[]) {
        if($storeId<=0)throw new InvalidArgumentException('Đơn vị không hợp lệ.');
        if(count($userIds)>100||count(array_unique($userIds))!==count($userIds))throw new InvalidArgumentException('Danh sách người nhận không hợp lệ.');
        foreach($userIds as $id)if(!is_int($id)||$id<=0)throw new InvalidArgumentException('Danh sách người nhận không hợp lệ.');
    }
    private function permission(string $code): string {
        // Only internal literals enter this SQL fragment.
        return "EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id AND r.status=1 WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND (r.code='ADMIN' OR EXISTS(SELECT 1 FROM dc_pm_role_permissions rp JOIN dc_pm_permissions pp ON pp.store_id=rp.store_id AND pp.id=rp.permission_id WHERE rp.store_id=r.store_id AND rp.role_id=r.id AND pp.code='$code')))";
    }
    private function pmRole(): string {
        return "EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id AND r.status=1 AND r.code='PM' WHERE ur.store_id=u.store_id AND ur.user_id=u.id)";
    }
    public function eligibleSql(string $kind): string {
        if($kind==='timesheet')return 'u.status=1 AND '.$this->permission('pm.timesheets.own')." AND NOT EXISTS(SELECT 1 FROM dc_pm_timesheets s WHERE s.store_id=u.store_id AND s.user_id=u.id AND s.work_date=? AND s.deleted_at IS NULL)";
        if($kind!=='task')throw new InvalidArgumentException('Loại nhắc việc không hợp lệ.');
        return 'u.status=1 AND '.$this->permission('pm.projects.view').' AND '.$this->permission('pm.tasks.view')." AND t.deleted_at IS NULL AND p.deleted_at IS NULL AND t.status<>'done' AND t.due_date IS NOT NULL AND t.due_date<=? AND ((t.assignee_id=u.id AND EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=t.store_id AND m.project_id=t.project_id AND m.user_id=u.id AND m.status=1)) OR (p.manager_id=u.id AND ".$this->pmRole().'))';
    }
    private function userScope(): array {
        if(!$this->userIds)return ['', '', []];
        return [' AND u.id IN ('.implode(',',array_fill(0,count($this->userIds),'?')).')',str_repeat('i',count($this->userIds)),$this->userIds];
    }
    public function validateUsers(): void {
        if(!$this->userIds)return;
        $rows=$this->db->fetchAll('SELECT id FROM dc_users WHERE store_id=? AND id IN ('.implode(',',array_fill(0,count($this->userIds),'?')).')','i'.str_repeat('i',count($this->userIds)),[$this->storeId,...$this->userIds]);
        if(count($rows)!==count($this->userIds))throw new InvalidArgumentException('Danh sách người nhận không hợp lệ.');
    }
    private function workSql(): string {
        return "(EXISTS(SELECT 1 FROM dc_pm_allocations a JOIN dc_pm_tasks allocation_task ON allocation_task.store_id=a.store_id AND allocation_task.id=a.task_id AND allocation_task.project_id=a.project_id JOIN dc_pm_projects ap ON ap.store_id=allocation_task.store_id AND ap.id=allocation_task.project_id WHERE a.store_id=u.store_id AND a.user_id=u.id AND a.work_date=? AND a.hours>0 AND a.deleted_at IS NULL AND allocation_task.deleted_at IS NULL AND allocation_task.status<>'done' AND ap.deleted_at IS NULL) OR EXISTS(SELECT 1 FROM dc_pm_tasks dt JOIN dc_pm_projects dp ON dp.store_id=dt.store_id AND dp.id=dt.project_id WHERE dt.store_id=u.store_id AND dt.assignee_id=u.id AND dt.status='in_progress' AND dt.deleted_at IS NULL AND dp.deleted_at IS NULL AND EXISTS(SELECT 1 FROM dc_pm_project_members dm WHERE dm.store_id=dt.store_id AND dm.project_id=dt.project_id AND dm.user_id=u.id AND dm.status=1)))";
    }
    public function candidates(string $job,string $date,int $afterUser=0,int $afterTask=0): array {
        [$scope,$scopeTypes,$scopeIds]=$this->userScope();
        if($job==='timesheet')return $this->db->fetchAll('SELECT u.id recipient_id,0 entity_id FROM dc_users u WHERE u.store_id=? AND u.id>? AND '.$this->eligibleSql($job).' AND '.$this->workSql().$scope.' ORDER BY u.id LIMIT 100','iiss'.$scopeTypes,[$this->storeId,$afterUser,$date,$date,...$scopeIds]);
        $end=(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');$start=(new DateTimeImmutable($date))->modify('-7 days')->format('Y-m-d');
        return $this->db->fetchAll('SELECT u.id recipient_id,t.id entity_id,t.due_date FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id JOIN dc_users u ON u.store_id=t.store_id AND (u.id=t.assignee_id OR u.id=p.manager_id) WHERE t.store_id=? AND (u.id>? OR (u.id=? AND t.id>?)) AND '.$this->eligibleSql($job).' AND t.due_date>=?'.$scope.' ORDER BY u.id,t.id LIMIT 100','iiiiss'.$scopeTypes,[$this->storeId,$afterUser,$afterUser,$afterTask,$end,$start,...$scopeIds]);
    }
    public function insert(array $row,string $job,string $kind,string $date): int {
        [$scope,$scopeTypes,$scopeIds]=$this->userScope();
        $params=[$kind,$date,(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'),$this->storeId,(int)$row['recipient_id']];
        $sql='INSERT INTO dc_pm_notifications(store_id,recipient_id,kind,entity_id,event_date,created_at,due_date) SELECT u.store_id,u.id,?,';
        if($job==='timesheet')return $this->db->execute($sql.'0,?,?,NULL FROM dc_users u WHERE u.store_id=? AND u.id=? AND '.$this->eligibleSql($job).' AND '.$this->workSql().$scope,'sssiiss'.$scopeTypes,[...$params,$date,$date,...$scopeIds]);
        $end=(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');$start=(new DateTimeImmutable($date))->modify('-7 days')->format('Y-m-d');
        return $this->db->execute($sql.'t.id,?,?,t.due_date FROM dc_users u JOIN dc_pm_tasks t ON t.store_id=u.store_id JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id WHERE u.store_id=? AND u.id=? AND t.id=? AND t.due_date=? AND '.$this->eligibleSql($job).' AND t.due_date>=?'.$scope,'sssiiisss'.$scopeTypes,[...$params,(int)$row['entity_id'],$row['due_date'],$end,$start,...$scopeIds]);
    }
    public function lockName(string $job): string {
        $database=$this->db->fetchOne('SELECT DATABASE() name')['name']??'';
        return 'pmremind:'.substr(hash('sha256',$database.':'.$this->storeId.':'.$job),0,50);
    }
    public function acquire(string $name): bool {return (int)($this->db->fetchOne('SELECT GET_LOCK(?,0) acquired','s',[$name])['acquired']??0)===1;}
    public function release(string $name): void {if((int)($this->db->fetchOne('SELECT RELEASE_LOCK(?) released','s',[$name])['released']??0)!==1)throw new RuntimeException('Lock release failed.');}
    public function preflight(bool $commit=false): void {
        $table=$this->db->fetchOne("SELECT ENGINE engine,TABLE_COLLATION collation_name FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications'");
        if(!$table||($table['engine']??null)!=='InnoDB'||($table['collation_name']??null)!=='utf8mb4_unicode_ci')throw new RuntimeException('Notification schema unavailable.');
        $columns=$this->db->fetchAll("SELECT COLUMN_NAME name,COLUMN_TYPE type,IS_NULLABLE nullable,EXTRA extra,COLUMN_DEFAULT default_value FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' ORDER BY ORDINAL_POSITION");
        $expected=['id'=>['bigint unsigned','NO'],'store_id'=>['bigint unsigned','NO'],'recipient_id'=>['int unsigned','NO'],'kind'=>['varchar(32)','NO'],'entity_id'=>['bigint unsigned','NO'],'event_date'=>['date','NO'],'read_at'=>['datetime','YES'],'created_at'=>['datetime','NO']];
        if($commit)$expected['due_date']=['date','YES'];
        if(!$commit)$columns=array_values(array_filter($columns,fn($c)=>$c['name']!=='due_date'));
        if(count($columns)!==count($expected))throw new RuntimeException('Notification schema mismatch.');
        foreach($columns as $c){$type=preg_replace('/\b(bigint|int)\([0-9]+\)/','$1',$c['type']);if(!isset($expected[$c['name']])||[$type,$c['nullable']]!==$expected[$c['name']])throw new RuntimeException('Notification column mismatch.');if($c['name']==='id'&&!str_contains($c['extra'],'auto_increment'))throw new RuntimeException('Notification identity mismatch.');}
        $defaults=array_column($columns,'default_value','name');
        if(!in_array($defaults['read_at']??null,[null,'NULL'],true)||!isset($defaults['entity_id'])||(string)$defaults['entity_id']!=='0'||!preg_match('/^current_timestamp(?:\(\))?$/i',(string)($defaults['created_at']??'')))throw new RuntimeException('Notification defaults mismatch.');
        if($commit&&!in_array($defaults['due_date']??null,[null,'NULL'],true))throw new RuntimeException('Notification deadline default mismatch.');
        $indexes=$this->db->fetchAll("SELECT INDEX_NAME name,NON_UNIQUE non_unique,GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) cols,SUM(SUB_PART IS NOT NULL) prefixes FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_pm_notifications' GROUP BY INDEX_NAME,NON_UNIQUE");
        $map=array_column($indexes,null,'name');
        if($commit&&(!isset($map['uq_pm_notification_deadline'])||$map['uq_pm_notification_deadline']['cols']!=='store_id,recipient_id,kind,entity_id,due_date'||(int)$map['uq_pm_notification_deadline']['non_unique']!==0||(int)($map['uq_pm_notification_deadline']['prefixes']??0)!==0))throw new RuntimeException('Notification deadline unique missing.');
        foreach(['PRIMARY'=>['id',0],'uq_pm_notification_event'=>['store_id,recipient_id,kind,entity_id,event_date',0],'idx_pm_notification_recipient'=>['store_id,recipient_id,read_at,id',1]] as $name=>$index)if(!isset($map[$name])||$map[$name]['cols']!==$index[0]||(int)$map[$name]['non_unique']!==$index[1]||(int)($map[$name]['prefixes']??0)!==0)throw new RuntimeException('Notification index mismatch.');
    }
    private function visible(string $date): array {
        $end=(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
        $join=' FROM dc_pm_notifications n JOIN dc_users u ON u.store_id=n.store_id AND u.id=n.recipient_id LEFT JOIN dc_pm_tasks t ON t.store_id=n.store_id AND t.id=n.entity_id LEFT JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id';
        // Timesheet reminders expire with the day; old task notices persist only while actionable.
        $where=' WHERE n.store_id=? AND n.recipient_id=? AND ((n.kind=\'timesheet_missing\' AND n.event_date=? AND '.$this->eligibleSql('timesheet').") OR (n.kind IN ('task_today','task_tomorrow','task_overdue') AND ".$this->eligibleSql('task').'))';
        return [$join.$where,[$date,$date,$end]];
    }
    public function list(int $actor,string $date,int $page=1,int $size=20): array {
        $page=max(1,min(100000,$page));$size=max(1,min(20,$size));[$sql,$dates]=$this->visible($date);$params=[$this->storeId,$actor,...$dates];
        $count=$this->db->fetchOne('SELECT COUNT(*) total,SUM(n.read_at IS NULL) unread'.$sql,'iisss',$params);
        $rows=$this->db->fetchAll('SELECT n.*,t.name task_name,t.project_id,t.due_date current_due_date'.$sql.' ORDER BY n.id DESC LIMIT ? OFFSET ?','iisssii',[...$params,$size,($page-1)*$size]);
        return ['rows'=>array_map(fn($r)=>(new PmNotificationInfo($r))->toArray(),$rows),'total'=>(int)($count['total']??0),'unread'=>(int)($count['unread']??0),'page'=>$page];
    }
    public function markRead(int $actor,int $id,string $date): bool {
        [$sql,$dates]=$this->visible($date);
        $row=$this->db->fetchOne('SELECT n.id'.$sql.' AND n.id=?','iisssi',[$this->storeId,$actor,...$dates,$id]);
        if(!$row)return false;
        $this->db->execute('UPDATE dc_pm_notifications SET read_at=COALESCE(read_at,?) WHERE store_id=? AND recipient_id=? AND id=?','siii',[(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s'),$this->storeId,$actor,$id]);return true;
    }
}
