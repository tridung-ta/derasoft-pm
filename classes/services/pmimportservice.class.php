<?php
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');
include_once(ROOT_PATH.'classes/dao/pmimports.class.php');
class PmImportService {
    public const HEADERS=['username','fullname','email','department_code','weekly_limit_hours','role_code'];
    private PmDb $db;private PmAccess $access;private mysqli $connection;
    public function __construct($database,private int $storeId,private int $actorId){$this->db=new PmDb($database);$this->connection=$database->connection;$this->access=new PmAccess($database,$storeId,$actorId);}
    private function permission(): void {
        if(!$this->access->hasRole('ADMIN')||!$this->access->hasPermission('pm.imports.manage')||!$this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$this->storeId,$this->actorId]))throw new DomainException('Chỉ Admin được kiểm tra import.');
    }
    private function inspect(string $path,string $name): void {
        if(strtolower(pathinfo($name,PATHINFO_EXTENSION))!=='xlsx'||!is_file($path)||filesize($path)>2097152||filesize($path)===0)throw new InvalidArgumentException('Chỉ nhận XLSX tối đa 2MB.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);if(!in_array($mime,['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],true))throw new InvalidArgumentException('MIME không hợp lệ.');
        $zip=new ZipArchive();if($zip->open($path)!==true)throw new InvalidArgumentException('Không đọc được XLSX.');
        try{
            if($zip->numFiles>200||$zip->locateName('xl/workbook.xml')===false||$zip->locateName('[Content_Types].xml')===false)throw new InvalidArgumentException('Cấu trúc XLSX không hợp lệ.');
            $total=0;
            for($i=0;$i<$zip->numFiles;$i++){
                $s=$zip->statIndex($i);$n=$s['name'];$total+=$s['size'];
                if($total>16777216||$s['size']>4194304||$s['size']>max(1,$s['comp_size'])*100||!empty($s['encryption_method']))throw new InvalidArgumentException('ZIP vượt ngân sách giải nén hoặc đã mã hóa.');
                if(str_contains($n,'..')||str_contains($n,'\\')||str_starts_with($n,'/')||preg_match('/vba|externalLinks|embeddings|activeX/i',$n))throw new InvalidArgumentException('Không nhận macro, liên kết ngoài hoặc nội dung nhúng.');
                if(preg_match('/\.(xml|rels)$/i',$n)){
                    $xml=$zip->getFromIndex($i);if($xml===false||str_contains($xml,"\0")||preg_match('/<!\s*(DOCTYPE|ENTITY)|TargetMode\s*=\s*["\x27]External|macroEnabled|vbaProject/i',$xml))throw new InvalidArgumentException('XML hoặc liên kết ngoài không an toàn.');
                }
            }
        }finally{$zip->close();}
    }
    public function preview(string $path,string $name): array {
        $this->permission();$this->inspect($path,$name);include_once(ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php');
        $reader=new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();$reader->setReadDataOnly(false);
        try{$info=$reader->listWorksheetInfo($path);}catch(Throwable $e){throw new InvalidArgumentException('Workbook không hợp lệ.');}
        if(count($info)!==1||$info[0]['totalRows']>501||$info[0]['totalColumns']>6)throw new InvalidArgumentException('Chỉ nhận 1 sheet, 6 cột, tối đa 500 dòng dữ liệu.');
        try{$book=$reader->load($path);}catch(Throwable $e){throw new InvalidArgumentException('Không đọc được workbook.');}
        try{
            $sheet=$book->getActiveSheet();$headers=[];foreach(range('A','F') as $c){$cell=$sheet->getCell($c.'1');if($cell->getDataType()==='f')throw new InvalidArgumentException('Không nhận công thức.');$headers[]=trim((string)$cell->getValue());}
            if($headers!==self::HEADERS)throw new InvalidArgumentException('Header phải đúng thứ tự theo file mẫu.');
            $rows=[];$errors=[];$seenUsers=[];$seenEmails=[];
            for($i=2;$i<=$sheet->getHighestDataRow();$i++){
                $values=[];$formula=false;foreach(range('A','F') as $c){$cell=$sheet->getCell($c.$i);$formula=$formula||$cell->getDataType()==='f';$values[]=trim((string)$cell->getValue());}
                if(implode('',$values)==='')continue;$r=array_combine(self::HEADERS,$values);$messages=[];
                if($formula)$messages[]='Không nhận công thức.';
                if(!preg_match('/^[A-Za-z0-9_.-]{3,50}$/D',$r['username']))$messages[]='Username không hợp lệ.';
                if($r['fullname']===''||mb_strlen($r['fullname'])>100||preg_match('/[\x00-\x1f\x7f]/',$r['fullname']))$messages[]='Tên không hợp lệ.';
                if(!filter_var($r['email'],FILTER_VALIDATE_EMAIL)||strlen($r['email'])>50)$messages[]='Email không hợp lệ hoặc vượt 50 ký tự của cột dc_users.email.';
                $userKey=mb_strtolower($r['username']);$emailKey=mb_strtolower($r['email']);
                if(isset($seenUsers[$userKey])||isset($seenEmails[$emailKey]))$messages[]='Trùng username/email trong file.';$seenUsers[$userKey]=true;$seenEmails[$emailKey]=true;
                if($this->db->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND (username=? OR email=?) LIMIT 1','iss',[$this->storeId,$r['username'],$r['email']]))$messages[]='Username/email đã tồn tại trong tenant.';
                if(!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D',$r['weekly_limit_hours'])||(float)$r['weekly_limit_hours']<=0||(float)$r['weekly_limit_hours']>168)$messages[]='Giới hạn tuần phải >0 và <=168.';
                if($r['role_code']!=='EMPLOYEE'||!$this->db->fetchOne("SELECT id FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE' AND status=1",'i',[$this->storeId]))$messages[]='Chỉ staging role EMPLOYEE active; không tự cấp quyền quản trị.';
                $r['department_id']=null;
                if($r['department_code']!==''){$d=$this->db->fetchOne('SELECT id FROM dc_pm_departments WHERE store_id=? AND code=? AND status=1 AND deleted_at IS NULL','is',[$this->storeId,$r['department_code']]);if(!$d)$messages[]='Phòng ban không active trong tenant.';else $r['department_id']=(int)$d['id'];}
                $r['row_number']=$i;$rows[]=$r;if($messages)$errors[]=['row'=>$i,'messages'=>$messages];
            }
            if(!$rows)$errors[]=['row'=>0,'messages'=>['File không có dữ liệu.']];
            return ['rows'=>$rows,'errors'=>$errors,'valid'=>$errors===[],'applied'=>false];
        }finally{$book->disconnectWorksheets();}
    }
    public function stage(string $path,string $name): array {
        $this->permission();$result=$this->preview($path,$name);if(!$result['valid'])return $result;
        $this->db->beginTransaction();try{
            $this->db->execute("INSERT INTO dc_pm_import_logs(store_id,actor_id,status,row_count) VALUES(?,?,'staged',?)",'iii',[$this->storeId,$this->actorId,count($result['rows'])]);$id=(int)$this->db->fetchOne('SELECT LAST_INSERT_ID() id')['id'];
            foreach($result['rows'] as $row)$this->db->execute('INSERT INTO dc_pm_import_staging(store_id,import_id,source_row,payload) VALUES(?,?,?,?)','iiis',[$this->storeId,$id,$row['row_number'],json_encode($row,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)]);
            $this->db->execute("INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,new_values) VALUES(?,?,'import',?,'stage',?)",'iiis',[$this->storeId,$this->actorId,$id,json_encode(['status'=>'staged','row_count'=>count($result['rows']),'applied'=>false],JSON_THROW_ON_ERROR)]);
            $this->db->commit();$result['import_id']=$id;return $result;
        }catch(Throwable $e){$this->db->rollBack();throw $e;}
    }
    public function history(): array {$this->permission();return $this->db->fetchAll('SELECT id,status,row_count,created_at FROM dc_pm_import_logs WHERE store_id=? ORDER BY id DESC LIMIT 50','i',[$this->storeId]);}
    public function detail(int $id): array {
        $this->permission();if($id<=0)throw new InvalidArgumentException('Batch không hợp lệ.');
        $dao=new PmImports((object)['connection'=>$this->connection]);
        return $this->batchDetail($dao,$id);
    }
    private function batchDetail(PmImports $dao,int $id): array {
        $batch=$dao->batch($this->storeId,$id);if(!$batch)throw new DomainException('Không tìm thấy batch trong tenant.');
        $batch['results']=$dao->results($this->storeId,$id);$batch['counts']=['applied'=>0,'skipped_duplicate'=>0,'failed'=>0,'pending'=>0];
        foreach($batch['results'] as $row)$batch['counts'][$row['status']]++;
        $batch['counts']['pending']+=(int)$batch['row_count']-count($batch['results']);return $batch;
    }
    public function apply(int $id,bool $resume=false): array {
        $this->permission();if($id<=0)throw new InvalidArgumentException('Batch không hợp lệ.');
        $dao=new PmImports((object)['connection'=>$this->connection]);$dao->requireEmailUnique();
        $lock=$dao->lockName($this->storeId,$id);if(!$dao->acquire($lock))throw new OverflowException('Batch đang được Admin khác áp dụng.');
        try{
            $batch=$dao->batch($this->storeId,$id);if(!$batch)throw new DomainException('Không tìm thấy batch trong tenant.');
            if($batch['status']==='completed')return $this->batchDetail($dao,$id);
            if(!in_array($batch['status'],['staged','partial','in_progress'],true))throw new InvalidArgumentException('Trạng thái batch không hợp lệ.');
            if($batch['status']!=='staged'&&!$resume)throw new InvalidArgumentException('Chọn tiếp tục batch để xử lý các dòng chưa hoàn tất.');
            $rows=$dao->rows($this->storeId,$id);if(count($rows)!==(int)$batch['row_count']||count($rows)<1||count($rows)>500)throw new RuntimeException('Invalid staging count.');
            $dao->setStatus($this->storeId,$id,'in_progress');
            foreach($rows as $row){
                $source=(int)$row['source_row'];$intent=$dao->intent($this->storeId,$id,$source,$row['payload']);
                if(in_array($intent['status'],['applied','skipped_duplicate'],true))continue;
                $dao->attempt($this->storeId,$id,$source);
                try{$r=json_decode($row['payload'],true,512,JSON_THROW_ON_ERROR);$roleId=$this->validateApplyRow($r);}
                catch(InvalidArgumentException|JsonException $e){$dao->outcome($this->storeId,$id,$source,'failed','invalid_row_or_reference');continue;}
                $marker=['store_id'=>$this->storeId,'import_id'=>$id,'source_row'=>$source,'token'=>$intent['provenance_token']];$recovered=false;
                try{$userId=$dao->insertUser($this->storeId,$r,$marker);}
                catch(mysqli_sql_exception $e){
                    if((int)$e->getCode()!==1062){$dao->outcome($this->storeId,$id,$source,'failed','insert_failed');continue;}
                    // Lookup only AFTER the unique-key rejection, to prove ownership.
                    $existing=$dao->duplicate($this->storeId,$r['email']);$properties=$existing?@unserialize($existing['properties'],['allowed_classes'=>false]):null;
                    if(!$existing||!is_array($properties)||($properties['pm_import']??null)!==$marker){$dao->outcome($this->storeId,$id,$source,'skipped_duplicate','duplicate_key');continue;}
                    $userId=(int)$existing['id'];$recovered=true;
                }
                $this->afterUserInsert();
                if($dao->usernameCollision($this->storeId,$r['username'],$userId)){$dao->outcome($this->storeId,$id,$source,'failed','username_collision_inactive',$userId);continue;}
                // Only PM journal/role/audit writes are transactional, never dc_users.
                $this->db->beginTransaction();try{
                    $this->db->execute('INSERT IGNORE INTO dc_pm_user_roles(store_id,user_id,role_id,is_primary) VALUES(?,?,?,1)','iii',[$this->storeId,$userId,$roleId]);
                    $dao->outcome($this->storeId,$id,$source,'applied',$recovered?'recovered_inactive':'created_inactive',$userId);
                    $this->db->execute("INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,new_values) VALUES(?,?,'import',?,'apply',?)",'iiis',[$this->storeId,$this->actorId,$id,json_encode(['source_row'=>$source,'status'=>'applied','user_id'=>$userId,'account_status'=>0],JSON_THROW_ON_ERROR)]);
                    $this->db->commit();
                }catch(Throwable $e){$this->db->rollBack();throw $e;}
            }
            $result=$this->batchDetail($dao,$id);$dao->setStatus($this->storeId,$id,($result['counts']['failed']+$result['counts']['pending'])>0?'partial':'completed');return $this->batchDetail($dao,$id);
        }finally{$dao->release($lock);}
    }
    protected function afterUserInsert(): void {}
    public function provisionPassword(int $id,int $row,string $password): array {
        $this->permission();if($id<=0||$row<2||strlen($password)<12||strlen($password)>72||str_contains($password,"\0"))throw new InvalidArgumentException('Mật khẩu cần 12–72 byte, không chứa ký tự NUL; batch/dòng phải hợp lệ.');
        $dao=new PmImports((object)['connection'=>$this->connection]);$lock=$dao->lockName($this->storeId,$id);
        if(!$dao->acquire($lock))throw new OverflowException('Batch đang được Admin khác xử lý.');
        try{
            $u=$dao->ownedUser($this->storeId,$id,$row);$p=$u?@unserialize($u['properties'],['allowed_classes'=>false]):null;
            $marker=['store_id'=>$this->storeId,'import_id'=>$id,'source_row'=>$row,'token'=>$u['provenance_token']??''];
            if(!$u||(int)$u['status']!==0||!is_array($p)||($p['pm_import']??null)!==$marker)throw new DomainException('Chỉ cấp mật khẩu cho tài khoản import của dòng này đang chưa kích hoạt.');
            if($dao->usernameCollision($this->storeId,$u['username'],(int)$u['id']))throw new InvalidArgumentException('Username đang trùng; chưa thể cấp mật khẩu.');
            if(!$dao->setPassword($this->storeId,(int)$u['id'],password_hash($password,PASSWORD_DEFAULT)))throw new DomainException('Trạng thái tài khoản đã thay đổi.');
            $this->db->execute("INSERT INTO dc_pm_audit_logs(store_id,actor_id,entity_type,entity_id,action,new_values) VALUES(?,?,'import',?,'provision_password',?)",'iiis',[$this->storeId,$this->actorId,$id,json_encode(['source_row'=>$row,'user_id'=>(int)$u['id'],'account_status'=>0],JSON_THROW_ON_ERROR)]);
            return $this->batchDetail($dao,$id);
        }finally{$dao->release($lock);}
    }
    private function validateApplyRow(mixed $r): int {
        if(!is_array($r)||!array_key_exists('department_id',$r)||($r['department_id']!==null&&(!is_int($r['department_id'])||$r['department_id']<=0)))throw new InvalidArgumentException('Invalid row.');
        foreach(self::HEADERS as $key)if(!isset($r[$key])||!is_string($r[$key]))throw new InvalidArgumentException('Invalid row.');
        if(!preg_match('/^[A-Za-z0-9_.-]{3,50}$/D',$r['username'])||$r['fullname']===''||mb_strlen($r['fullname'])>100||preg_match('/[\x00-\x1f\x7f]/',$r['fullname'])||!filter_var($r['email'],FILTER_VALIDATE_EMAIL)||strlen($r['email'])>50||!preg_match('/^\d{1,3}(?:\.\d{1,2})?$/D',$r['weekly_limit_hours'])||(float)$r['weekly_limit_hours']<=0||(float)$r['weekly_limit_hours']>168||$r['role_code']!=='EMPLOYEE')throw new InvalidArgumentException('Invalid row.');
        $role=$this->db->fetchOne("SELECT id FROM dc_pm_roles WHERE store_id=? AND code='EMPLOYEE' AND status=1",'i',[$this->storeId]);if(!$role)throw new InvalidArgumentException('Inactive role.');
        if($r['department_code']!==''){
            $d=$this->db->fetchOne('SELECT id FROM dc_pm_departments WHERE store_id=? AND code=? AND status=1 AND deleted_at IS NULL','is',[$this->storeId,$r['department_code']]);
            if(!$d||(int)$d['id']!==($r['department_id']??null))throw new InvalidArgumentException('Inactive department.');
        }elseif(($r['department_id']??null)!==null)throw new InvalidArgumentException('Invalid department.');
        return (int)$role['id'];
    }
    public function template(): string {
        $this->permission();include_once(ROOT_PATH.'classes/PhpSpreadSheet/PhpOffice/autoload.php');$book=new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        foreach(self::HEADERS as $i=>$h)$book->getActiveSheet()->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1).'1',$h,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $path=tempnam(sys_get_temp_dir(),'pm-import-template-');if($path===false)throw new RuntimeException('Không tạo được file tạm.');
        try{(new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save($path);$bytes=file_get_contents($path);if($bytes===false)throw new RuntimeException('Không đọc được mẫu.');return $bytes;}finally{$book->disconnectWorksheets();if(is_file($path))unlink($path);}
    }
}
