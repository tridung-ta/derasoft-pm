<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
/** Account events intentionally contain no contact or credential values. */
class PmAccountAuditService {
    private PmDb $db;
    public function __construct($database,private int $storeId,private int $actorId){$this->db=new PmDb($database);}
    public function profileChanged(array $fields): void {
        $allowed=['fullname','email','address','tel'];
        foreach($fields as $field)if(!is_string($field)||!in_array($field,$allowed,true))throw new InvalidArgumentException('Trường thông tin hồ sơ cần ghi nhật ký không hợp lệ.');
        $fields=array_values(array_unique($fields));sort($fields);
        if(!$fields)return;
        $this->write('profile_update',json_encode(['changed_fields'=>$fields],JSON_THROW_ON_ERROR));
    }
    public function passwordChanged(): void {$this->write('password_change',null);}
    private function write(string $action,?string $metadata): void {
        $this->db->execute('INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,new_values) VALUES(?,?,?,?,?,?)','iisiss',[$this->storeId,$this->actorId,'account',$this->actorId,$action,$metadata]);
    }
}
