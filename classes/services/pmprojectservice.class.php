<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');

class PmProjectService {
    private PmDb $db;
    private PmAccess $access;
    public function __construct($database,private int $storeId,private int $actorId){
        $this->db=new PmDb($database);$this->access=new PmAccess($database,$storeId,$actorId);
    }
    private function permission(string $code): void {
        if(!$this->access->hasPermission($code))throw new DomainException('Bạn không có quyền thực hiện thao tác này.');
    }
    public function getProject(int $id,bool $lock=false): array {
        $this->permission('pm.projects.view');
        $row=$this->db->fetchOne('SELECT * FROM dc_pm_projects WHERE store_id=? AND id=? AND deleted_at IS NULL'.($lock?' FOR UPDATE':''),'ii',[$this->storeId,$id]);
        if(!$row)throw new OutOfBoundsException('Không tìm thấy dự án.');
        if(!$this->access->hasRole('ADMIN') && (int)$row['manager_id']!==$this->actorId && !$this->db->fetchOne('SELECT id FROM dc_pm_project_members WHERE store_id=? AND project_id=? AND user_id=? AND status=1','iii',[$this->storeId,$id,$this->actorId]))throw new DomainException('Bạn không thuộc dự án này.');
        return $row;
    }
    public function canManage(array $project): bool {
        return $this->access->hasRole('ADMIN')||($this->access->hasRole('PM')&&(int)$project['manager_id']===$this->actorId);
    }
    private function requireManage(array $project,string $permission): void {
        $this->permission($permission);if(!$this->canManage($project))throw new DomainException('Bạn chỉ có thể quản lý dự án mình phụ trách.');
    }
    public function listProjects(string $q='',int $page=1): array {
        $this->permission('pm.projects.view');
        $where='p.store_id=? AND p.deleted_at IS NULL';$types='i';$params=[$this->storeId];
        if(!$this->access->hasRole('ADMIN')){$where.=' AND (p.manager_id=? OR EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=p.store_id AND m.project_id=p.id AND m.user_id=? AND m.status=1))';$types.='ii';array_push($params,$this->actorId,$this->actorId);}
        if($q!==''){$where.=' AND (p.name LIKE ? OR p.code LIKE ?)';$types.='ss';array_push($params,'%'.$q.'%','%'.$q.'%');}
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) total FROM dc_pm_projects p WHERE '.$where,$types,$params)['total'];
        $rows=$this->db->fetchAll('SELECT p.*,u.fullname manager_name FROM dc_pm_projects p LEFT JOIN dc_users u ON u.store_id=p.store_id AND u.id=p.manager_id WHERE '.$where.' ORDER BY p.id DESC LIMIT 20 OFFSET ?',$types.'i',[...$params,(max(1,$page)-1)*20]);
        $canViewTasks=$this->access->hasPermission('pm.tasks.view');
        $counts=[];
        if($rows && $canViewTasks){
            // One prepared aggregate for the visible page, never one query per card.
            $ids=array_map(static fn(array $row): int=>(int)$row['id'],$rows);
            $placeholders=implode(',',array_fill(0,count($ids),'?'));
            foreach($this->db->fetchAll("SELECT project_id,COUNT(*) task_total,SUM(status='done') task_done FROM dc_pm_tasks WHERE store_id=? AND deleted_at IS NULL AND project_id IN ($placeholders) GROUP BY project_id",'i'.str_repeat('i',count($ids)),[$this->storeId,...$ids]) as $count){
                $counts[(int)$count['project_id']]=$count;
            }
        }
        foreach($rows as &$row){
            $count=$counts[(int)$row['id']]??[];
            $row['task_total']=$canViewTasks?(int)($count['task_total']??0):null;
            $row['task_done']=$canViewTasks?(int)($count['task_done']??0):null;
            $row['progress_percent']=$row['task_total']>0?round(100*$row['task_done']/$row['task_total'],1):null;
        }unset($row);
        return ['rows'=>$rows,'total'=>$total];
    }
    public function availableUsers(bool $managersOnly=false): array {
        $where=$managersOnly?" AND EXISTS(SELECT 1 FROM dc_pm_user_roles ur INNER JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.status=1 AND r.code IN ('ADMIN','PM'))":'';
        return $this->db->fetchAll('SELECT u.id,u.fullname FROM dc_users u WHERE u.store_id=? AND u.status=1'.$where.' ORDER BY u.fullname,u.id','i',[$this->storeId]);
    }
    private function date(?string $value): ?string {
        if($value===null||$value==='')return null;
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$date||$date->format('Y-m-d')!==$value)throw new InvalidArgumentException('Ngày không hợp lệ.');
        return $value;
    }
    private function number($value,int $digits): string {
        $value=(string)$value;
        if(!preg_match('/^\d{1,'.$digits.'}(?:\.\d{1,2})?$/D',$value))throw new InvalidArgumentException('Số tiền hoặc số giờ không hợp lệ.');
        return $value;
    }
    public function saveProject(array $data,?int $id=null): int {
        $this->permission('pm.projects.manage');
        if(!$this->access->hasRole('ADMIN')&&!$this->access->hasRole('PM'))throw new DomainException('Chỉ Admin hoặc PM được tạo dự án.');
        $name=trim((string)($data['name']??''));$code=strtoupper(trim((string)($data['code']??'')));$description=(string)($data['description']??'');
        if($name===''||mb_strlen($name)>150||!preg_match('/^[A-Z0-9_-]{2,50}$/D',$code)||mb_strlen($description)>10000)throw new InvalidArgumentException('Tên, mã hoặc mô tả dự án không hợp lệ.');
        $status=(string)($data['status']??'planned');if(!in_array($status,['planned','active','paused','completed'],true))throw new InvalidArgumentException('Trạng thái dự án không hợp lệ.');
        $start=$this->date($data['start_date']??null);$end=$this->date($data['end_date']??null);if($start&&$end&&$end<$start)throw new InvalidArgumentException('Ngày kết thúc phải sau ngày bắt đầu.');
        $budget=$this->number($data['budget']??'0',13);
        $client=trim((string)($data['client_name']??''));if(mb_strlen($client)>150)throw new InvalidArgumentException('Tên khách hàng quá dài.');
        $manager=$this->access->hasRole('ADMIN')?(int)($data['manager_id']??0):$this->actorId;
        if(!in_array($manager,array_map('intval',array_column($this->availableUsers(true),'id')),true))throw new InvalidArgumentException('Người phụ trách phải là Admin/PM đang hoạt động trong tenant.');
        $this->db->beginTransaction();
        try{
            if($id){$project=$this->getProject($id,true);$this->requireManage($project,'pm.projects.manage');}
            if($this->db->fetchOne('SELECT id FROM dc_pm_projects WHERE store_id=? AND code=? AND id<>?','isi',[$this->storeId,$code,$id??0]))throw new DomainException('Mã dự án đã tồn tại.');
            if($id){if(!array_key_exists('client_name',$data))$client=$project['client_name'];$this->db->execute('UPDATE dc_pm_projects SET code=?,name=?,description=?,manager_id=?,start_date=?,end_date=?,budget=?,status=?,client_name=? WHERE store_id=? AND id=?','sssisssssii',[$code,$name,$description,$manager,$start,$end,$budget,$status,$client?:null,$this->storeId,$id]);}
            else{$this->db->execute('INSERT INTO dc_pm_projects(store_id,code,name,description,manager_id,start_date,end_date,budget,status,created_by,client_name) VALUES(?,?,?,?,?,?,?,?,?,?,?)','isssissssis',[$this->storeId,$code,$name,$description,$manager,$start,$end,$budget,$status,$this->actorId,$client?:null]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
            $this->db->execute('INSERT INTO dc_pm_project_members(store_id,project_id,user_id,status) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE status=1','iii',[$this->storeId,$id,$manager]);
            $this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function deleteProject(int $id): void {
        $this->db->beginTransaction();try{$project=$this->getProject($id,true);$this->requireManage($project,'pm.projects.manage');$this->db->execute('UPDATE dc_pm_projects SET deleted_at=NOW() WHERE store_id=? AND id=?','ii',[$this->storeId,$id]);$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function members(int $projectId): array {
        $this->getProject($projectId);
        return $this->db->fetchAll('SELECT m.user_id,m.project_role,u.fullname,u.status user_status FROM dc_pm_project_members m INNER JOIN dc_users u ON u.store_id=m.store_id AND u.id=m.user_id WHERE m.store_id=? AND m.project_id=? AND m.status=1 ORDER BY u.fullname','ii',[$this->storeId,$projectId]);
    }
    public function setMember(int $projectId,int $userId,bool $active,?string $projectRole=null): void {
        if($userId<=0)throw new InvalidArgumentException('Thành viên không hợp lệ.');
        if($projectRole!==null&&!in_array($projectRole,['manager','developer','qa','analyst','other'],true))throw new InvalidArgumentException('Vai trò trong dự án không hợp lệ.');
        $this->db->beginTransaction();try{
            $project=$this->getProject($projectId,true);$this->requireManage($project,'pm.project_members.manage');
            if(!$active&&!$this->db->fetchOne('SELECT id FROM dc_pm_project_members WHERE store_id=? AND project_id=? AND user_id=? AND status=1','iii',[$this->storeId,$projectId,$userId]))throw new OutOfBoundsException('Không tìm thấy thành viên trong dự án.');
            if(!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->storeId,$userId])&&$active)throw new InvalidArgumentException('Thành viên phải đang hoạt động trong tenant.');
            if(!$active){if((int)$project['manager_id']===$userId)throw new DomainException('Không thể gỡ người phụ trách dự án.');if($this->db->fetchOne('SELECT id FROM dc_pm_tasks WHERE store_id=? AND project_id=? AND assignee_id=? AND deleted_at IS NULL LIMIT 1','iii',[$this->storeId,$projectId,$userId]))throw new DomainException('Cần giao lại hoặc bỏ người được giao của các task trước khi gỡ thành viên.');}
            $this->db->execute('INSERT INTO dc_pm_project_members(store_id,project_id,user_id,status,project_role) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),project_role=COALESCE(VALUES(project_role),project_role)','iiiis',[$this->storeId,$projectId,$userId,$active?1:0,$projectRole]);$this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function tasks(int $projectId): array {
        $this->permission('pm.tasks.view');$this->getProject($projectId);
        return $this->db->fetchAll('SELECT t.*,u.fullname assignee_name FROM dc_pm_tasks t LEFT JOIN dc_users u ON u.store_id=t.store_id AND u.id=t.assignee_id WHERE t.store_id=? AND t.project_id=? AND t.deleted_at IS NULL ORDER BY t.id DESC','ii',[$this->storeId,$projectId]);
    }
    public function saveTask(int $projectId,array $data,?int $id=null): int {
        $name=trim((string)($data['name']??''));$description=(string)($data['description']??'');$status=(string)($data['status']??'todo');$priority=(string)($data['priority']??'normal');
        if($name===''||mb_strlen($name)>150||mb_strlen($description)>10000||!in_array($status,['todo','in_progress','review','done'],true)||!in_array($priority,['low','normal','high'],true))throw new InvalidArgumentException('Thông tin task không hợp lệ.');
        $hours=$this->number($data['estimated_hours']??'0',6);$due=$this->date($data['due_date']??null);$assignee=(int)($data['assignee_id']??0);$assignee=$assignee?:null;
        $this->db->beginTransaction();try{
            $project=$this->getProject($projectId,true);$this->requireManage($project,'pm.tasks.manage');
            if($assignee&&!$this->db->fetchOne('SELECT m.id FROM dc_pm_project_members m INNER JOIN dc_users u ON u.store_id=m.store_id AND u.id=m.user_id AND u.status=1 WHERE m.store_id=? AND m.project_id=? AND m.user_id=? AND m.status=1','iii',[$this->storeId,$projectId,$assignee]))throw new InvalidArgumentException('Task chỉ được giao cho thành viên active của dự án.');
            $old=$id?$this->db->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND project_id=? AND id=? AND deleted_at IS NULL FOR UPDATE','iii',[$this->storeId,$projectId,$id]):null;
            if($id&&!$old)throw new OutOfBoundsException('Không tìm thấy task.');
            $start=$this->date($data['start_date']??($old['start_date']??null));if($start&&$due&&$start>$due)throw new InvalidArgumentException('Ngày bắt đầu phải trước hạn hoàn thành.');
            if($id)$this->db->execute('UPDATE dc_pm_tasks SET name=?,description=?,assignee_id=?,status=?,priority=?,estimated_hours=?,due_date=? WHERE store_id=? AND project_id=? AND id=?','ssissssiii',[$name,$description,$assignee,$status,$priority,$hours,$due,$this->storeId,$projectId,$id]);
            else{$this->db->execute('INSERT INTO dc_pm_tasks(store_id,project_id,name,description,assignee_id,status,priority,estimated_hours,due_date,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)','iississssi',[$this->storeId,$projectId,$name,$description,$assignee,$status,$priority,$hours,$due,$this->actorId]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];}
            $completed=$status==='done'?($old&&$old['status']==='done'?$old['completed_at']:(new DateTimeImmutable('now',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d H:i:s')):null;
            $this->db->execute('UPDATE dc_pm_tasks SET start_date=?,completed_at=? WHERE store_id=? AND project_id=? AND id=?','ssiii',[$start,$completed,$this->storeId,$projectId,$id]);
            $this->auditTask($id,$old?'update':'create',$old,$this->taskSnapshot($projectId,$id));
            $this->db->commit();return $id;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function deleteTask(int $projectId,int $id): void {
        $this->db->beginTransaction();try{$project=$this->getProject($projectId,true);$this->requireManage($project,'pm.tasks.manage');$old=$this->taskSnapshot($projectId,$id);if($this->db->execute('UPDATE dc_pm_tasks SET deleted_at=NOW() WHERE store_id=? AND project_id=? AND id=? AND deleted_at IS NULL','iii',[$this->storeId,$projectId,$id])!==1)throw new OutOfBoundsException('Không tìm thấy task.');$this->auditTask($id,'soft_delete',$old,$this->taskSnapshot($projectId,$id));$this->db->commit();}catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    private function taskSnapshot(int $projectId,int $id): array {
        $row=$this->db->fetchOne('SELECT * FROM dc_pm_tasks WHERE store_id=? AND project_id=? AND id=? FOR UPDATE','iii',[$this->storeId,$projectId,$id]);
        if(!$row)throw new OutOfBoundsException('Không tìm thấy task.');return $row;
    }
    private function auditTask(int $id,string $action,?array $old,array $new): void {
        $this->db->execute('INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,old_values,new_values) VALUES(?,?,?,?,?,?,?)','iisisss',[$this->storeId,$this->actorId,'task',$id,$action,$old===null?null:json_encode($old,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),json_encode($new,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
    }
}
