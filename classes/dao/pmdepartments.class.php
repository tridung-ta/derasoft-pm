<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
class PmDepartments {
    private PmDb $db;
    public function __construct($database){$this->db=new PmDb($database);}
    public function getActive(int $storeId): array {
        return $this->db->fetchAll('SELECT id,code,name FROM dc_pm_departments WHERE store_id=? AND status=1 AND deleted_at IS NULL ORDER BY name','i',[$storeId]);
    }
    public function exists(int $storeId,int $id): bool {
        return $this->db->fetchOne('SELECT 1 FROM dc_pm_departments WHERE store_id=? AND id=? AND status=1 AND deleted_at IS NULL','ii',[$storeId,$id])!==null;
    }
    public function list(int $storeId): array {
        return $this->db->fetchAll('SELECT id,code,name,status FROM dc_pm_departments WHERE store_id=? AND deleted_at IS NULL ORDER BY name,id','i',[$storeId]);
    }
    public function codeExists(int $storeId,string $code,?int $excludeId=null): bool {
        $sql='SELECT 1 FROM dc_pm_departments WHERE store_id=? AND code=?';$types='is';$params=[$storeId,$code];
        if($excludeId){$sql.=' AND id<>?';$types.='i';$params[]=$excludeId;}
        return $this->db->fetchOne($sql.' LIMIT 1',$types,$params)!==null;
    }
    public function listHidden(int $storeId): array {
        return $this->db->fetchAll('SELECT id,code,name FROM dc_pm_departments WHERE store_id=? AND deleted_at IS NOT NULL ORDER BY name,id','i',[$storeId]);
    }
    public function hiddenCodeExists(int $storeId,string $code,?int $excludeId=null): bool {
        $sql='SELECT 1 FROM dc_pm_departments WHERE store_id=? AND code=? AND deleted_at IS NOT NULL';$types='is';$params=[$storeId,$code];
        if($excludeId){$sql.=' AND id<>?';$types.='i';$params[]=$excludeId;}
        return $this->db->fetchOne($sql.' LIMIT 1',$types,$params)!==null;
    }
    public function restore(int $storeId,int $id): bool {
        return $this->db->execute('UPDATE dc_pm_departments SET status=1,deleted_at=NULL,updated_at=NOW() WHERE store_id=? AND id=? AND deleted_at IS NOT NULL','ii',[$storeId,$id])===1;
    }
    public function save(int $storeId,?int $id,string $code,string $name): int {
        if($id){
            $current=$this->db->fetchOne('SELECT id FROM dc_pm_departments WHERE store_id=? AND id=? AND deleted_at IS NULL','ii',[$storeId,$id]);
            if(!$current)throw new OutOfBoundsException('Không tìm thấy phòng ban trong tenant hiện tại.');
            $affected=$this->db->execute('UPDATE dc_pm_departments SET code=?,name=?,updated_at=NOW() WHERE store_id=? AND id=? AND deleted_at IS NULL','ssii',[$code,$name,$storeId,$id]);
            if($affected<0)throw new RuntimeException('Không thể cập nhật phòng ban.');
            return $id;
        }
        $this->db->execute('INSERT INTO dc_pm_departments(store_id,code,name,status,created_at,updated_at) VALUES(?,?,?,1,NOW(),NOW())','iss',[$storeId,$code,$name]);
        return (int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
    }
    public function setStatus(int $storeId,int $id,int $status): bool {
        return $this->db->execute('UPDATE dc_pm_departments SET status=?,updated_at=NOW() WHERE store_id=? AND id=? AND deleted_at IS NULL','iii',[$status,$storeId,$id])===1;
    }
    public function softDelete(int $storeId,int $id): bool {
        $assigned=$this->db->fetchOne('SELECT 1 FROM dc_users WHERE store_id=? AND department_id=? AND status<>2 LIMIT 1','ii',[$storeId,$id]);
        if($assigned)throw new DomainException('Không thể ẩn phòng ban đang có nhân sự.');
        return $this->db->execute('UPDATE dc_pm_departments SET status=0,deleted_at=NOW(),updated_at=NOW() WHERE store_id=? AND id=? AND deleted_at IS NULL','ii',[$storeId,$id])===1;
    }
}
