<?php
include_once(ROOT_PATH.'classes/dao/pmcosts.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');

class PmCostService {
    private PmCosts $costs; private PmAccess $access;
    public function __construct($database,private int $storeId,private int $actorId){$this->costs=new PmCosts($database);$this->access=new PmAccess($database,$storeId,$actorId);}
    private function date($value): ?string {
        if($value===null||$value==='')return null;
        if(!is_string($value))throw new InvalidArgumentException('Ngày không hợp lệ.');
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
        if(!$date||$date->format('Y-m-d')!==$value)throw new InvalidArgumentException('Ngày không hợp lệ.');
        return $value;
    }
    private function actual(array $f,string $group,array &$warnings): array {
        $currencies=[];
        foreach($this->costs->actualCurrencies($f,$group) as $r)$currencies[(string)$r['bucket']][]=$r['currency'];
        $safe=[];
        foreach($currencies as $bucket=>$codes){if(count($codes)===1&&preg_match('/^[A-Z]{3}$/D',$codes[0]))$safe[]=(string)$bucket;}
        $amounts=[];
        foreach($this->costs->actualAmounts($f,$group,$safe) as $r)$amounts[(string)$r['bucket']]=$r;
        $rows=[];
        foreach($this->costs->actualHours($f,$group) as $r){
            $key=(string)$r['bucket'];$codes=$currencies[$key];$valid=isset($amounts[$key]);
            $warning=$valid?null:'Khác currency hoặc currency không hợp lệ ('.implode(', ',$codes).'): không cộng chi phí cho '.$group.' #'.$key.'.';
            if($warning)$warnings[]=$warning;
            $rows[$key]=array_merge($r,['cost'=>$valid?(string)$amounts[$key]['cost']:null,'currency'=>$valid?$codes[0]:null,'currencies'=>$codes,'warning'=>$warning]);
        }
        return $rows;
    }
    public function dashboard(array $input=[]): array {
        if(!$this->access->hasPermission('pm.costs.view')||!($this->access->hasRole('ADMIN')||$this->access->hasRole('PM')))throw new DomainException('Bạn không có quyền xem chi phí.');
        foreach(['page','project_id'] as $key)if(isset($input[$key])&&(!is_scalar($input[$key])||!preg_match('/^\d{1,9}$/D',(string)$input[$key])))throw new InvalidArgumentException('Bộ lọc không hợp lệ.');
        $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');
        $f=['store_id'=>$this->storeId,'manager_id'=>$this->access->hasRole('ADMIN')?null:$this->actorId,'project_id'=>(int)($input['project_id']??0),'from'=>$this->date($input['from']??null),'to'=>$this->date($input['to']??null),'valuation_date'=>$today];
        if($f['from']&&$f['to']&&$f['from']>$f['to'])throw new InvalidArgumentException('Ngày bắt đầu phải trước ngày kết thúc.');
        $db=$this->costs->db();
        // Guard and SUM must see the same rows, including during concurrent polling/writes.
        if(!in_array($db->fetchOne('SELECT @@transaction_isolation isolation_level')['isolation_level'],['REPEATABLE-READ','SERIALIZABLE'],true))throw new RuntimeException('Cost snapshot requires repeatable-read isolation.');
        $db->beginTransaction();
        try{
            if($f['project_id']&&!$this->costs->projectExists($f))throw new DomainException('Dự án không tồn tại hoặc ngoài phạm vi của bạn.');
            $warnings=['Phòng ban/role dùng phân loại hiện tại; timesheet chưa có snapshot phòng ban/role lịch sử.'];
            $groups=[];foreach(['total','project','task','user','department','role','date'] as $group)$groups[$group]=$this->actual($f,$group,$warnings);
            $lifetime=$f;$lifetime['from']=null;$lifetime['to']=null;
            $allActual=$this->actual($lifetime,'project',$warnings);
            $estimateRows=$this->costs->estimateRows($f);$estimateCurrencies=[];$missing=[];
            foreach($estimateRows as $r){$pid=(int)$r['project_id'];if($r['currency']===null||$r['assignee_id']===null){$missing[$pid]=true;$warnings[]='Task #'.$r['id'].' thiếu assignee hoặc đơn giá; ước tính chưa đầy đủ.';}else{$estimateCurrencies[$pid][$r['currency']]=true;}}
            $safeProjects=[];foreach($estimateCurrencies as $pid=>$codes){if(count($codes)===1&&preg_match('/^[A-Z]{3}$/D',(string)array_key_first($codes))&&!isset($missing[$pid]))$safeProjects[]=$pid;else $warnings[]='Ước tính dự án #'.$pid.' thiếu dữ liệu hoặc khác currency: không cộng tiền.';}
            $estimates=[];foreach($this->costs->estimateAmounts($f,$safeProjects) as $r)$estimates[(int)$r['project_id']]=$r;
            $projects=$this->costs->projects($f,max(1,(int)($input['page']??1)));
            foreach($projects['rows'] as &$p){$pid=(int)$p['id'];$p['actual']=$groups['project'][(string)$pid]??self::emptyActual();$p['lifetime']=$allActual[(string)$pid]??self::emptyActual();$p['estimate']=$estimates[$pid]??['cost'=>isset($missing[$pid])||isset($estimateCurrencies[$pid])?null:'0.00','currency'=>'VND'];$p['comparable']=$p['lifetime']['cost']!==null&&$p['lifetime']['currency']==='VND'&&$p['estimate']['cost']!==null&&$p['estimate']['currency']==='VND';if(!$p['comparable'])$warnings[]='Dự án #'.$pid.': ngân sách VND không so sánh với số tiền thiếu dữ liệu hoặc khác currency.';}unset($p);
            $tasks=$this->costs->taskNames($f);$taskEstimates=[];foreach($estimateRows as $r)$taskEstimates[(int)$r['id']]=$r;
            foreach($tasks as &$t){$t['actual']=$groups['task'][(string)$t['id']]??self::emptyActual();$t['estimate']=$taskEstimates[(int)$t['id']]??null;}unset($t);
            $summary=$groups['total']['0']??self::emptyActual();
            $estimateCodes=[];foreach($estimates as $r)$estimateCodes[$r['currency']]=true;
            $estimateTotal=null;$estimateCurrency=count($estimateCodes)===1?array_key_first($estimateCodes):null;
            if(!$missing&&count($safeProjects)===count($estimateCurrencies)&&count($estimateCodes)<=1)$estimateTotal=$this->costs->decimalSum(array_column($estimates,'cost'));
            else $warnings[]='Tổng ước tính thiếu dữ liệu hoặc khác currency: không cộng tiền.';
            $result=['filters'=>['from'=>$f['from']??'','to'=>$f['to']??'','project_id'=>$f['project_id']],'valuation_date'=>$today,'summary'=>$summary,'estimate_total'=>$estimateTotal,'estimate_currency'=>$estimateCurrency??($estimateTotal!==null?'VND':null),'projects'=>$projects['rows'],'tasks'=>$f['project_id']?$tasks:[],'groups'=>$groups,'warnings'=>array_values(array_unique($warnings)),'page'=>$projects['page'],'total_pages'=>max(1,(int)ceil($projects['total']/20))];
            $db->commit();return $result;
        }catch(Throwable $error){$db->rollBack();throw $error;}
    }
    private static function emptyActual(): array {return ['entries'=>0,'hours'=>'0.00','regular_hours'=>'0.00','ot_hours'=>'0.00','cost'=>'0.00','currency'=>'VND','currencies'=>[],'warning'=>null];}
}
