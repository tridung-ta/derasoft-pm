<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
class PmReports {
    private PmDb $db;
    public function __construct($database){$this->db=new PmDb($database);}
    public function hours(array $f,bool $export=false): array {
        $where='s.store_id=? AND s.deleted_at IS NULL AND s.work_date BETWEEN ? AND ?';$types='iss';$params=[$f['store_id'],$f['from'],$f['to']];
        if($f['scope']==='pm'){$where.=' AND p.manager_id=?';$types.='i';$params[]=$f['actor_id'];}
        elseif($f['scope']==='own'){$where.=' AND s.user_id=?';$types.='i';$params[]=$f['actor_id'];}
        foreach(['project_id','user_id'] as $key)if($f[$key]!==null){$where.=' AND s.'.$key.'=?';$types.='i';$params[]=$f[$key];}
        $join=' FROM dc_pm_timesheets s JOIN dc_pm_projects p ON p.store_id=s.store_id AND p.id=s.project_id JOIN dc_pm_tasks t ON t.store_id=s.store_id AND t.project_id=s.project_id AND t.id=s.task_id JOIN dc_users u ON u.store_id=s.store_id AND u.id=s.user_id WHERE '.$where;
        $summary=$this->db->fetchOne('SELECT COUNT(*) entries,COALESCE(SUM(s.hours),0) hours,COALESCE(SUM(s.regular_hours),0) regular_hours,COALESCE(SUM(s.ot_hours),0) ot_hours'.$join,$types,$params);
        $total=(int)$summary['entries'];if($export&&$total>5000)throw new InvalidArgumentException('Export tối đa 5000 dòng. Vui lòng thu hẹp bộ lọc.');
        $pages=max(1,(int)ceil($total/50));$page=min($f['page'],$pages);$limit=$export?5000:50;$offset=$export?0:($page-1)*50;
        $rows=$this->db->fetchAll('SELECT s.id,s.work_date,s.shift_label,s.hours,s.regular_hours,s.ot_hours,s.description,s.user_id,u.fullname user_name,s.project_id,p.name project_name,t.name task_name,(p.deleted_at IS NOT NULL OR t.deleted_at IS NOT NULL) historical'.$join.' ORDER BY s.work_date,s.id LIMIT ? OFFSET ?',$types.'ii',[...$params,$limit,$offset]);
        $groups=$this->db->fetchAll('SELECT s.user_id,u.fullname user_name,SUM(s.hours) hours,SUM(s.regular_hours) regular_hours,SUM(s.ot_hours) ot_hours'.$join.' GROUP BY s.user_id,u.fullname ORDER BY s.user_id',$types,$params);
        return ['rows'=>$rows,'summary'=>$summary,'by_user'=>$groups,'page'=>$page,'total_pages'=>$pages];
    }
}
