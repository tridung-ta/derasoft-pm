<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
class PmUserIdentityService {
    private PmDb $db;
    private ?string $collation=null;
    public function __construct($database){$this->db=new PmDb($database);}
    public function lockName(int $storeId): string {
        if($storeId<=0)throw new InvalidArgumentException('Tenant không hợp lệ.');
        $name=$this->db->fetchOne('SELECT DATABASE() name')['name'];
        return 'pmidentity:'.substr(hash('sha256',$name.':'.$storeId),0,50);
    }
    public function acquire(int $storeId,int $timeout=2): string {
        $name=$this->lockName($storeId);
        if((int)$this->db->fetchOne('SELECT GET_LOCK(?,?) acquired','si',[$name,$timeout])['acquired']!==1)throw new OverflowException('Nhân sự của đơn vị đang được cập nhật. Vui lòng thử lại.');
        return $name;
    }
    public function release(string $name): void {$this->db->fetchOne('SELECT RELEASE_LOCK(?) released','s',[$name]);}
    public function conflicts(int $storeId,string $username,string $email,int $exclude=0,bool $importInsert=false): bool {
        // Import keeps native INSERT/1062 for email duplication and recovery.
        // The identical username+email pair also reaches 1062; other identity
        // conflicts must be rejected before a MyISAM INSERT can persist.
        $where=$importInsert?"((username<>'' AND username=? AND NOT(email <=> ?)) OR (username<>'' AND username=?) OR (email<>'' AND email=?))":"((username<>'' AND username=?) OR (username<>'' AND username=?) OR (email<>'' AND email=?) OR (email<>'' AND email=?))";
        $params=$importInsert?[$storeId,$exclude,$username,$email,$email,$username]:[$storeId,$exclude,$username,$email,$username,$email];
        return $this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id<>? AND '.$where.' LIMIT 1','iissss',$params)!==null;
    }
    public function assertAvailable(int $storeId,string $username,string $email,int $exclude=0): void {
        if($this->conflicts($storeId,$username,$email,$exclude))throw new DomainException('Tên đăng nhập hoặc email trùng định danh của tài khoản khác trong đơn vị.');
    }
    public function equivalent(string $left,string $right): bool {
        if($left===''||$right==='')return false;
        return $this->identityKey($left)===$this->identityKey($right);
    }
    public function identityKey(string $value): string {
        if($this->collation===null){
            $rows=$this->db->fetchAll("SELECT COLLATION_NAME name FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='dc_users' AND COLUMN_NAME IN ('username','email')");
            if(count($rows)!==2||$rows[0]['name']!==$rows[1]['name']||!preg_match('/^utf8mb4_[a-z0-9_]+$/D',$rows[0]['name']))throw new RuntimeException('Unsupported identity collation.');
            $this->collation=$rows[0]['name'];
        }
        $c=$this->collation;
        // All accepted identities fit 50 characters. Padding to the same length
        // matches the PAD SPACE collation used by the actual legacy columns.
        return $this->db->fetchOne('SELECT HEX(WEIGHT_STRING(CONVERT(? USING utf8mb4) COLLATE '.$c.' AS CHAR(50))) identity_key','s',[$value])['identity_key'];
    }
    public function scanPairs(): array {
        return $this->db->fetchAll("SELECT a.store_id,a.id user_a,b.id user_b,
            (a.username<>'' AND b.username<>'' AND a.username=b.username) username_username,
            (a.email<>'' AND b.email<>'' AND a.email=b.email) email_email,
            (a.username<>'' AND b.email<>'' AND a.username=b.email) username_email,
            (a.email<>'' AND b.username<>'' AND a.email=b.username) email_username
            FROM dc_users a JOIN dc_users b ON b.store_id=a.store_id AND b.id>a.id
            WHERE (a.username<>'' AND b.username<>'' AND a.username=b.username)
               OR (a.email<>'' AND b.email<>'' AND a.email=b.email)
               OR (a.username<>'' AND b.email<>'' AND a.username=b.email)
               OR (a.email<>'' AND b.username<>'' AND a.email=b.username)
            ORDER BY a.store_id,a.id,b.id");
    }
}
