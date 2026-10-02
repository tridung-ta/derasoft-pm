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
}
