<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

/** Fixed grouping expressions and prepared inputs; role/membership joins cannot multiply rows. */
class PmCosts {
    private PmDb $db;
    public function __construct($database) { $this->db=new PmDb($database); }
    public function db(): PmDb { return $this->db; }

    private function scope(array $f,string $alias='p'): array {
        $where="$alias.store_id=? AND $alias.deleted_at IS NULL";$types='i';$params=[$f['store_id']];
        if($f['manager_id']!==null){$where.=" AND $alias.manager_id=?";$types.='i';$params[]=$f['manager_id'];}
        if($f['project_id']){$where.=" AND $alias.id=?";$types.='i';$params[]=$f['project_id'];}
        return [$where,$types,$params];
    }
    public function projects(array $f,int $page): array {
        [$where,$types,$params]=$this->scope($f);
        $total=(int)$this->db->fetchOne("SELECT COUNT(*) total FROM dc_pm_projects p WHERE $where",$types,$params)['total'];
        $page=min($page,max(1,(int)ceil($total/20)));
        $rows=$this->db->fetchAll("SELECT p.id,p.name,p.budget FROM dc_pm_projects p WHERE $where ORDER BY p.id LIMIT 20 OFFSET ?",$types.'i',[...$params,($page-1)*20]);
        return ['rows'=>$rows,'total'=>$total,'page'=>$page];
    }
    public function projectExists(array $f): bool {
        [$where,$types,$params]=$this->scope($f);
        return $this->db->fetchOne("SELECT p.id FROM dc_pm_projects p WHERE $where",$types,$params)!==null;
    }
    private function actualQuery(array $f,string $group): array {
        $expressions=['total'=>'0','project'=>'s.project_id','task'=>'s.task_id','user'=>'s.user_id','department'=>'COALESCE(u.department_id,0)',
            'role'=>"COALESCE((SELECT ur.role_id FROM dc_pm_user_roles ur WHERE ur.store_id=s.store_id AND ur.user_id=s.user_id AND ur.is_primary=1 ORDER BY ur.role_id LIMIT 1),0)",'date'=>'s.work_date'];
        if(!isset($expressions[$group]))throw new InvalidArgumentException('Invalid cost grouping.');
        [$where,$types,$params]=$this->scope($f);
        $where.=' AND s.deleted_at IS NULL';
        if($f['from']!==null){$where.=' AND s.work_date>=?';$types.='s';$params[]=$f['from'];}
        if($f['to']!==null){$where.=' AND s.work_date<=?';$types.='s';$params[]=$f['to'];}
        [$extra,$extraTypes,$extraParams]=$this->personFilter($f,'s.user_id');$where.=$extra;$types.=$extraTypes;array_push($params,...$extraParams);
        $from="dc_pm_timesheets s JOIN dc_pm_projects p ON p.store_id=s.store_id AND p.id=s.project_id LEFT JOIN dc_users u ON u.store_id=s.store_id AND u.id=s.user_id";
        return [$from,$where,$types,$params,$expressions[$group]];
    }
    /** No SUM(cost) is executed here; service inspects all currencies first. */
    public function actualCurrencies(array $f,string $group): array {
        [$from,$where,$types,$params,$bucket]=$this->actualQuery($f,$group);
        return $this->db->fetchAll("SELECT DISTINCT $bucket bucket,s.currency FROM $from WHERE $where ORDER BY bucket,s.currency",$types,$params);
    }
    public function actualHours(array $f,string $group): array {
        [$from,$where,$types,$params,$bucket]=$this->actualQuery($f,$group);
        return $this->db->fetchAll("SELECT $bucket bucket,COUNT(*) entries,SUM(s.hours) hours,SUM(s.regular_hours) regular_hours,SUM(s.ot_hours) ot_hours FROM $from WHERE $where GROUP BY bucket ORDER BY bucket",$types,$params);
    }
    /** Only buckets already verified by the service as single-currency may be summed. */
    public function actualAmounts(array $f,string $group,array $safeBuckets): array {
        if(!$safeBuckets)return [];
        [$from,$where,$types,$params,$bucket]=$this->actualQuery($f,$group);
        $where.=" AND $bucket IN (".implode(',',array_fill(0,count($safeBuckets),'?')).')';
        $types.=str_repeat('s',count($safeBuckets));$params=[...$params,...$safeBuckets];
        return $this->db->fetchAll("SELECT $bucket bucket,s.currency,SUM(s.cost) cost FROM $from WHERE $where GROUP BY bucket,s.currency ORDER BY bucket",$types,$params);
    }
    private function estimateQuery(array $f): array {
        [$where,$types,$params]=$this->scope($f);
        [$extra,$extraTypes,$extraParams]=$this->personFilter($f,'t.assignee_id');$where.=$extra;$types.=$extraTypes;array_push($params,...$extraParams);
        // Resolve exactly one user rate or primary-role rate, matching PmRateService priority.
        $rateId="COALESCE((SELECT hr.id FROM dc_pm_hourly_rates hr WHERE hr.store_id=t.store_id AND hr.user_id=t.assignee_id AND hr.status=1 AND hr.effective_from<=? AND (hr.effective_to IS NULL OR hr.effective_to>=?) ORDER BY hr.effective_from DESC,hr.id DESC LIMIT 1),
            (SELECT hr.id FROM dc_pm_hourly_rates hr JOIN dc_pm_user_roles ur ON ur.store_id=hr.store_id AND ur.role_id=hr.role_id WHERE hr.store_id=t.store_id AND ur.user_id=t.assignee_id AND ur.is_primary=1 AND hr.status=1 AND hr.effective_from<=? AND (hr.effective_to IS NULL OR hr.effective_to>=?) ORDER BY hr.effective_from DESC,hr.id DESC LIMIT 1))";
        $sql="SELECT t.id,t.project_id,t.name,t.assignee_id,t.estimated_hours,t.deleted_at,r.currency,r.rate,CAST(t.estimated_hours*r.rate AS DECIMAL(30,2)) estimate FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id LEFT JOIN dc_pm_hourly_rates r ON r.store_id=t.store_id AND r.id=$rateId WHERE $where AND t.deleted_at IS NULL";
        return [$sql,'ssss'.$types,[$f['valuation_date'],$f['valuation_date'],$f['valuation_date'],$f['valuation_date'],...$params]];
    }
    public function estimateRows(array $f): array {
        [$sql,$types,$params]=$this->estimateQuery($f);
        return $this->db->fetchAll($sql.' ORDER BY t.id',$types,$params);
    }
    public function estimateAmounts(array $f,array $safeProjects): array {
        if(!$safeProjects)return [];
        [$sql,$types,$params]=$this->estimateQuery($f);
        $placeholders=implode(',',array_fill(0,count($safeProjects),'?'));
        return $this->db->fetchAll("SELECT e.project_id,e.currency,SUM(e.estimate) cost FROM ($sql) e WHERE e.project_id IN ($placeholders) GROUP BY e.project_id,e.currency",$types.str_repeat('i',count($safeProjects)),[...$params,...$safeProjects]);
    }
    public function decimalSum(array $values): string {
        // Amounts have already been checked for homogeneous currency in the service.
        if(!$values)return '0.00';
        return (string)$this->db->fetchOne('SELECT '.implode('+',array_fill(0,count($values),'CAST(? AS DECIMAL(30,2))')).' total',str_repeat('s',count($values)),$values)['total'];
    }
    public function taskNames(array $f): array {
        [$where,$types,$params]=$this->scope($f);
        return $this->db->fetchAll("SELECT t.id,t.name,t.project_id,t.deleted_at FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id WHERE $where ORDER BY t.id",$types,$params);
    }
    private function personFilter(array $f,string $column): array {
        $where='';$types='';$params=[];
        if(!empty($f['user_id'])){$where.=" AND $column=?";$types.='i';$params[]=$f['user_id'];}
        if(!empty($f['role_code'])){$where.=" AND EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=p.store_id AND ur.user_id=$column AND r.status=1 AND r.code=?)";$types.='s';$params[]=$f['role_code'];}
        return [$where,$types,$params];
    }
}
