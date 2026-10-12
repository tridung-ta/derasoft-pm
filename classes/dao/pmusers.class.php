<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/security/pmidinput.class.php');
include_once(ROOT_PATH.'classes/services/pmuserinputservice.class.php');
include_once(ROOT_PATH.'classes/services/pmuseridentityservice.class.php');
class PmUsers {
    private PmDb $db;
    private PmUserIdentityService $identity;
    public function __construct($database){$this->db=new PmDb($database);$this->identity=new PmUserIdentityService($database);}
    public function list(int $storeId,array $filters,int $page=1,int $limit=20): array {
        $where=['u.store_id=?','u.status<>2'];$types='i';$params=[$storeId];
        if(($filters['q']??'')!==''){$where[]='(u.fullname LIKE ? OR u.email LIKE ? OR u.username LIKE ?)';$term='%'.$filters['q'].'%';$types.='sss';array_push($params,$term,$term,$term);}
        if(!empty($filters['department_id'])){$where[]='u.department_id=?';$types.='i';$params[]=(int)$filters['department_id'];}
        if(!empty($filters['role_id'])){$where[]='EXISTS(SELECT 1 FROM dc_pm_user_roles fur WHERE fur.store_id=u.store_id AND fur.user_id=u.id AND fur.role_id=?)';$types.='i';$params[]=(int)$filters['role_id'];}
        $offset=max(0,($page-1)*$limit);$types.='ii';array_push($params,$limit,$offset);
        return $this->db->fetchAll('SELECT u.id,u.username,u.fullname,u.email,u.tel,u.cell,u.department_id,u.weekly_limit_hours,u.status,d.name department_name
            FROM dc_users u LEFT JOIN dc_pm_departments d ON d.store_id=u.store_id AND d.id=u.department_id
            WHERE '.implode(' AND ',$where).' ORDER BY u.fullname,u.id LIMIT ? OFFSET ?',$types,$params);
    }
    public function count(int $storeId,array $filters=[]): int {
        $sql='SELECT COUNT(*) total FROM dc_users WHERE store_id=? AND status<>2';$types='i';$params=[$storeId];
        $q=(string)($filters['q']??'');
        if($q!==''){$sql.=' AND (fullname LIKE ? OR email LIKE ? OR username LIKE ?)';$term='%'.$q.'%';$types.='sss';array_push($params,$term,$term,$term);}
        if(!empty($filters['department_id'])){$sql.=' AND department_id=?';$types.='i';$params[]=(int)$filters['department_id'];}
        if(!empty($filters['role_id'])){$sql.=' AND EXISTS(SELECT 1 FROM dc_pm_user_roles fur WHERE fur.store_id=dc_users.store_id AND fur.user_id=dc_users.id AND fur.role_id=?)';$types.='i';$params[]=(int)$filters['role_id'];}
        return (int)$this->db->fetchOne($sql,$types,$params)['total'];
    }
    public function get(int $storeId,int $id): ?array {
        return $this->db->fetchOne('SELECT id,username,fullname,email,tel,cell,department_id,weekly_limit_hours,status FROM dc_users WHERE store_id=? AND id=? AND status<>2','ii',[$storeId,$id]);
    }
    public function emailExists(int $storeId,string $email,?int $exclude=null): bool {
        $sql='SELECT 1 FROM dc_users WHERE store_id=? AND email=? AND status<>2';$types='is';$params=[$storeId,$email];
        if($exclude){$sql.=' AND id<>?';$types.='i';$params[]=$exclude;}
        return $this->db->fetchOne($sql.' LIMIT 1',$types,$params)!==null;
    }
    public function usernameExists(int $storeId,string $username): bool {
        return $this->db->fetchOne('SELECT 1 FROM dc_users WHERE store_id=? AND username=? AND status<>2 LIMIT 1','is',[$storeId,$username])!==null;
    }
    public function create(int $storeId,array $data): int {
        PmUserInputService::validateFields((string)$data['username'],(string)$data['fullname'],(string)$data['email'],(string)$data['tel']);
        PmUserInputService::validateCreate((string)$data['email'],(string)$data['password'],(string)$data['tel']);
        $lock=$this->identity->acquire($storeId);
        try{
        $this->identity->assertAvailable($storeId,(string)$data['username'],(string)$data['email']);
        $hash=password_hash((string)$data['password'],PASSWORD_DEFAULT);
        $this->db->execute('INSERT INTO dc_users(store_id,username,password,password_hash,email,fullname,tel,department_id,weekly_limit_hours,type,date_created,status,properties)
            VALUES(?,?,NULL,?,?,?,?,?,?,1,NOW(),1,?)','isssssids',[
                $storeId,(string)$data['username'],$hash,(string)$data['email'],(string)$data['fullname'],
                (string)$data['tel'],$data['department_id'],(float)$data['weekly_limit_hours'],serialize([])
            ]);
        $row=$this->db->fetchOne('SELECT LAST_INSERT_ID() id');
        return (int)$row['id'];
        }finally{$this->identity->release($lock);}
    }
    public function updateSafe(int $storeId,int $id,array $data): bool {
        PmUserInputService::validateFields('',(string)$data['fullname'],(string)$data['email'],(string)$data['tel'],true,false);
        $lock=$this->identity->acquire($storeId);
        try{
        $old=$this->get($storeId,$id);if(!$old)return false;
        $this->identity->assertAvailable($storeId,(string)$old['username'],(string)$data['email'],$id);
        $affected=$this->db->execute('UPDATE dc_users SET fullname=?,email=?,tel=?,department_id=?,weekly_limit_hours=? WHERE store_id=? AND id=? AND status<>2',
            'sssidii',[(string)$data['fullname'],(string)$data['email'],(string)$data['tel'],$data['department_id'],(float)$data['weekly_limit_hours'],$storeId,$id]);
        // Zero changed rows is valid for an unchanged form, but not a missing,
        // hidden or cross-tenant target. Keep both cases distinct.
        return $affected>0||($affected===0&&$this->get($storeId,$id)!==null);
        }finally{$this->identity->release($lock);}
    }
    public function existsInStore(int $storeId,int $id): bool {
        return $this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=?','ii',[$storeId,$id])!==null;
    }
    public function setStatus(int $storeId,int $id,int $status): bool {
        return $this->db->execute('UPDATE dc_users SET status=? WHERE store_id=? AND id=?','iii',[$status,$storeId,$id])===1;
    }
    public function assignRoles(int $storeId,int $userId,array $roleIds,int $primaryRoleId): void {
        PmIdInput::parse($userId);PmIdInput::parse($primaryRoleId);
        foreach($roleIds as $roleId)PmIdInput::parse($roleId);
        if(!$this->get($storeId,$userId))throw new OutOfBoundsException('Không tìm thấy nhân sự trong đơn vị hiện tại.');
        $roleIds=array_values(array_unique(array_map('intval',$roleIds)));
        foreach(array_unique([...$roleIds,$primaryRoleId]) as $roleId){
            $role=$this->db->fetchOne('SELECT id,status FROM dc_pm_roles WHERE store_id=? AND id=?','ii',[$storeId,$roleId]);
            if(!$role)throw new OutOfBoundsException('Không tìm thấy vai trò trong đơn vị hiện tại.');
            if((int)$role['status']!==1)throw new InvalidArgumentException('Vai trò không hợp lệ hoặc đã ngừng hoạt động.');
        }
        if(!in_array($primaryRoleId,$roleIds,true))throw new InvalidArgumentException('Vai trò chính phải nằm trong danh sách vai trò được gán.');
        $this->db->beginTransaction();
        try{
            $this->db->execute('DELETE FROM dc_pm_user_roles WHERE store_id=? AND user_id=?','ii',[$storeId,$userId]);
            foreach(array_unique(array_map('intval',$roleIds)) as $roleId){
                $role=$this->db->fetchOne('SELECT id FROM dc_pm_roles WHERE store_id=? AND id=? AND status=1','ii',[$storeId,$roleId]);
                if(!$role)throw new InvalidArgumentException('Vai trò không hợp lệ hoặc đã ngừng hoạt động.');
                $this->db->execute('INSERT INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(?,?,?,?)','iiii',[$storeId,$userId,$roleId,$roleId===$primaryRoleId?1:0]);
            }
            if(!in_array($primaryRoleId,$roleIds,true))throw new InvalidArgumentException('Vai trò chính phải nằm trong danh sách vai trò được gán.');
            $this->db->commit();
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function roles(int $storeId,int $userId): array {
        return $this->db->fetchAll('SELECT role_id,is_primary FROM dc_pm_user_roles WHERE store_id=? AND user_id=?','ii',[$storeId,$userId]);
    }
    public function rolesForUsers(int $storeId,array $userIds): array {
        if(count($userIds)>100)throw new InvalidArgumentException('Mỗi lần chỉ được cập nhật vai trò cho tối đa 100 nhân sự.');
        foreach($userIds as $id)if(!is_int($id)||$id<=0)throw new InvalidArgumentException('Mã nhân sự không hợp lệ.');
        $userIds=array_values(array_unique($userIds));$grouped=array_fill_keys($userIds,[]);
        if(!$userIds)return $grouped;
        $placeholders=implode(',',array_fill(0,count($userIds),'?'));
        $rows=$this->db->fetchAll('SELECT user_id,role_id,is_primary FROM dc_pm_user_roles WHERE store_id=? AND user_id IN ('.$placeholders.') ORDER BY user_id,role_id','i'.str_repeat('i',count($userIds)),[$storeId,...$userIds]);
        foreach($rows as $row)$grouped[(int)$row['user_id']][]=['role_id'=>$row['role_id'],'is_primary'=>$row['is_primary']];
        return $grouped;
    }
}
