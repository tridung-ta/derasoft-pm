<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
class PmImports {
    private PmDb $db;
    public function __construct($database){$this->db=new PmDb($database);}
    public function requireEmailUnique(): void {
        $rows=$this->db->fetchAll("SELECT index_name AS index_name,column_name AS column_name,non_unique AS non_unique,sub_part AS sub_part FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='dc_users' ORDER BY index_name,seq_in_index");
        $indexes=[];$invalid=[];foreach($rows as $r){$indexes[$r['index_name']][]=$r['column_name'];if((int)$r['non_unique']!==0||$r['sub_part']!==null)$invalid[$r['index_name']]=true;}
        foreach($indexes as $name=>$columns)if(!isset($invalid[$name])&&($columns===['email']||$columns===['store_id','email']||$columns===['email','store_id']))return;
        throw new RuntimeException('Apply requires full DB email UNIQUE.');
    }
    public function lockName(int $storeId,int $id): string {
        $name=$this->db->fetchOne('SELECT DATABASE() name')['name'];return 'pmimport:'.substr(hash('sha256',$name.':'.$storeId.':'.$id),0,54);
    }
    public function acquire(string $name): bool {return (int)$this->db->fetchOne('SELECT GET_LOCK(?,0) acquired','s',[$name])['acquired']===1;}
    public function release(string $name): void {$this->db->fetchOne('SELECT RELEASE_LOCK(?) released','s',[$name]);}
    public function batch(int $storeId,int $id): ?array {return $this->db->fetchOne('SELECT id,status,row_count,created_at FROM dc_pm_import_logs WHERE store_id=? AND id=?','ii',[$storeId,$id]);}
    public function rows(int $storeId,int $id): array {return $this->db->fetchAll('SELECT source_row,payload FROM dc_pm_import_staging WHERE store_id=? AND import_id=? ORDER BY source_row','ii',[$storeId,$id]);}
    public function results(int $storeId,int $id): array {return $this->db->fetchAll('SELECT r.source_row,r.status,r.reason_code,r.user_id,r.attempt_count,u.status account_status FROM dc_pm_import_results r LEFT JOIN dc_users u ON u.store_id=r.store_id AND u.id=r.user_id WHERE r.store_id=? AND r.import_id=? ORDER BY r.source_row','ii',[$storeId,$id]);}
    public function setStatus(int $storeId,int $id,string $status): void {$this->db->execute('UPDATE dc_pm_import_logs SET status=? WHERE store_id=? AND id=?','sii',[$status,$storeId,$id]);}
    public function intent(int $storeId,int $id,int $row,string $payload): array {
        $this->db->execute('INSERT IGNORE INTO dc_pm_import_results(store_id,import_id,source_row,provenance_token,payload_sha256) VALUES(?,?,?,?,?)','iiiss',[$storeId,$id,$row,bin2hex(random_bytes(32)),hash('sha256',$payload)]);
        $r=$this->db->fetchOne('SELECT status,provenance_token,payload_sha256 FROM dc_pm_import_results WHERE store_id=? AND import_id=? AND source_row=?','iii',[$storeId,$id,$row]);
        if(!$r||!hash_equals($r['payload_sha256'],hash('sha256',$payload)))throw new RuntimeException('Immutable import payload changed.');return $r;
    }
    public function attempt(int $storeId,int $id,int $row): void {$this->db->execute("UPDATE dc_pm_import_results SET status='pending',reason_code='pending',attempt_count=attempt_count+1 WHERE store_id=? AND import_id=? AND source_row=?",'iii',[$storeId,$id,$row]);}
    public function outcome(int $storeId,int $id,int $row,string $status,string $reason,?int $userId=null): void {$this->db->execute('UPDATE dc_pm_import_results SET status=?,reason_code=?,user_id=? WHERE store_id=? AND import_id=? AND source_row=?','ssiiii',[$status,$reason,$userId,$storeId,$id,$row]);}
    public function insertUser(int $storeId,array $r,array $marker): int {
        // Intent is durable before this MyISAM write. No SELECT duplicate precheck.
        $hash=password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT);
        $this->db->execute('INSERT INTO dc_users(store_id,username,password,password_hash,email,fullname,tel,department_id,weekly_limit_hours,type,date_created,status,properties) VALUES(?,?,NULL,?,?,?,?,?,?,1,NOW(),0,?)','isssssiss',[$storeId,$r['username'],$hash,$r['email'],$r['fullname'],'',$r['department_id'],$r['weekly_limit_hours'],serialize(['pm_import'=>$marker])]);
        return (int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
    }
    public function duplicate(int $storeId,string $email): ?array {return $this->db->fetchOne('SELECT id,properties FROM dc_users WHERE store_id=? AND email=? LIMIT 1','is',[$storeId,$email]);}
    public function usernameCollision(int $storeId,string $username,int $userId): bool {return $this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND username=? AND id<>? LIMIT 1','isi',[$storeId,$username,$userId])!==null;}
    public function ownedUser(int $storeId,int $id,int $row): ?array {return $this->db->fetchOne("SELECT u.id,u.username,u.properties,u.status,r.provenance_token FROM dc_pm_import_results r JOIN dc_users u ON u.store_id=r.store_id AND u.id=r.user_id WHERE r.store_id=? AND r.import_id=? AND r.source_row=? AND r.status='applied'",'iii',[$storeId,$id,$row]);}
    public function setPassword(int $storeId,int $userId,string $hash): bool {return $this->db->execute('UPDATE dc_users SET password=NULL,password_hash=? WHERE store_id=? AND id=? AND status=0','sii',[$hash,$storeId,$userId])===1;}
}
