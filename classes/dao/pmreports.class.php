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
        if(!empty($f['role_code'])){$where.=" AND EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=s.store_id AND ur.user_id=s.user_id AND r.status=1 AND r.code=?)";$types.='s';$params[]=$f['role_code'];}
        $join=' FROM dc_pm_timesheets s JOIN dc_pm_projects p ON p.store_id=s.store_id AND p.id=s.project_id JOIN dc_pm_tasks t ON t.store_id=s.store_id AND t.project_id=s.project_id AND t.id=s.task_id JOIN dc_users u ON u.store_id=s.store_id AND u.id=s.user_id WHERE '.$where;
        $summary=$this->db->fetchOne('SELECT COUNT(*) entries,COALESCE(SUM(s.hours),0) hours,COALESCE(SUM(s.regular_hours),0) regular_hours,COALESCE(SUM(s.ot_hours),0) ot_hours'.$join,$types,$params);
        $total=(int)$summary['entries'];if($export&&$total>5000)throw new InvalidArgumentException('Export tối đa 5000 dòng. Vui lòng thu hẹp bộ lọc.');
        $pages=max(1,(int)ceil($total/50));$page=min($f['page'],$pages);$limit=$export?5000:50;$offset=$export?0:($page-1)*50;
        $rows=$this->db->fetchAll('SELECT s.id,s.work_date,s.shift_label,s.hours,s.regular_hours,s.ot_hours,s.description,s.user_id,u.fullname user_name,s.project_id,p.name project_name,t.name task_name,(p.deleted_at IS NOT NULL OR t.deleted_at IS NOT NULL) historical'.$join.' ORDER BY s.work_date,s.id LIMIT ? OFFSET ?',$types.'ii',[...$params,$limit,$offset]);
        $groups=$this->db->fetchAll('SELECT s.user_id,u.fullname user_name,SUM(s.hours) hours,SUM(s.regular_hours) regular_hours,SUM(s.ot_hours) ot_hours'.$join.' GROUP BY s.user_id,u.fullname ORDER BY s.user_id',$types,$params);
        $weeks=$this->db->fetchAll('SELECT DATE_SUB(s.work_date,INTERVAL WEEKDAY(s.work_date) DAY) week_start,s.user_id,u.fullname user_name,SUM(s.hours) hours,SUM(s.regular_hours) regular_hours,SUM(s.ot_hours) ot_hours'.$join.' GROUP BY week_start,s.user_id,u.fullname ORDER BY week_start,s.user_id LIMIT 5001',$types,$params);
        if(count($weeks)>5000)throw new InvalidArgumentException('Tổng hợp tuần tối đa 5000 dòng; thu hẹp bộ lọc.');
        return ['rows'=>$rows,'summary'=>$summary,'by_user'=>$groups,'by_week'=>$weeks,'page'=>$page,'total_pages'=>$pages];
    }
    public function tasks(array $f,bool $export=false): array {
        $where='t.store_id=? AND t.deleted_at IS NULL AND p.deleted_at IS NULL';$types='i';$params=[$f['store_id']];
        if($f['scope']==='pm'){$where.=' AND p.manager_id=?';$types.='i';$params[]=$f['actor_id'];}
        elseif($f['scope']==='own'){$where.=' AND t.assignee_id=?';$types.='i';$params[]=$f['actor_id'];}
        if($f['project_id']!==null){$where.=' AND t.project_id=?';$types.='i';$params[]=$f['project_id'];}
        if($f['user_id']!==null){$where.=' AND t.assignee_id=?';$types.='i';$params[]=$f['user_id'];}
        if(!empty($f['role_code'])){$where.=" AND EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=t.store_id AND ur.user_id=t.assignee_id AND r.status=1 AND r.code=?)";$types.='s';$params[]=$f['role_code'];}
        $where.=' AND (DATE(t.completed_at) BETWEEN ? AND ? OR t.due_date BETWEEN ? AND ?)';$types.='ssss';array_push($params,$f['from'],$f['to'],$f['from'],$f['to']);
        $today=(new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')))->format('Y-m-d');$asOf=min($today,$f['to']);
        $select="SELECT t.id,t.project_id,p.name project_name,t.assignee_id,u.fullname user_name,t.name,t.status,t.start_date,t.due_date,t.completed_at,(t.status='done' AND DATE(t.completed_at) BETWEEN ? AND ?) completed_in_period,(t.status<>'done' AND t.due_date<?) overdue,(t.status='done' AND DATE(t.completed_at)>t.due_date) completed_late,(t.status='done' AND t.completed_at IS NULL) completion_unknown";
        $join=' FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id LEFT JOIN dc_users u ON u.store_id=t.store_id AND u.id=t.assignee_id WHERE '.$where;
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) total'.$join,$types,$params)['total'];
        if($export&&$total>5000)throw new InvalidArgumentException('Export tối đa 5000 task; thu hẹp bộ lọc.');
        $page=min($f['page'],max(1,(int)ceil($total/50)));
        $rows=$this->db->fetchAll($select.$join.' ORDER BY t.project_id,t.id LIMIT ? OFFSET ?','sss'.$types.'ii',[$f['from'],$f['to'],$asOf,...$params,$export?5000:50,$export?0:($page-1)*50]);
        $summary=$this->db->fetchOne("SELECT COUNT(*) tasks,COALESCE(SUM(t.status='done' AND DATE(t.completed_at) BETWEEN ? AND ?),0) completed,COALESCE(SUM(t.status<>'done' AND t.due_date<?),0) overdue,COALESCE(SUM(t.status='done' AND DATE(t.completed_at)>t.due_date),0) completed_late,COALESCE(SUM(t.status='done' AND t.completed_at IS NULL),0) completion_unknown".$join,'sss'.$types,[$f['from'],$f['to'],$asOf,...$params]);
        $groups=$this->db->fetchAll("SELECT t.project_id,p.name project_name,t.assignee_id,u.fullname user_name,COUNT(*) tasks,COALESCE(SUM(t.status='done' AND DATE(t.completed_at) BETWEEN ? AND ?),0) completed,COALESCE(SUM(t.status<>'done' AND t.due_date<?),0) overdue".$join.' GROUP BY t.project_id,p.name,t.assignee_id,u.fullname ORDER BY t.project_id,t.assignee_id LIMIT 5001','sss'.$types,[$f['from'],$f['to'],$asOf,...$params]);
        if(count($groups)>5000)throw new InvalidArgumentException('Tổng hợp tối đa 5000 dòng.');
        foreach($rows as &$row){$end=$row['status']==='done'?($row['completed_at']?substr($row['completed_at'],0,10):null):$asOf;$row['late_days']=$row['due_date']&&$end?max(0,(int)(new DateTimeImmutable($row['due_date']))->diff(new DateTimeImmutable($end))->format('%r%a')):null;}unset($row);
        $weeks=$this->db->fetchAll("SELECT DATE_SUB(DATE(t.completed_at),INTERVAL WEEKDAY(t.completed_at) DAY) week_start,t.assignee_id,u.fullname user_name,COUNT(*) completed".$join." AND t.status='done' AND DATE(t.completed_at) BETWEEN ? AND ? GROUP BY week_start,t.assignee_id,u.fullname ORDER BY week_start,t.assignee_id LIMIT 5001",$types.'ss',[...$params,$f['from'],$f['to']]);
        if(count($weeks)>5000)throw new InvalidArgumentException('Tổng hợp tuần tối đa 5000 dòng.');
        return ['rows'=>$rows,'summary'=>$summary,'by_project_user'=>$groups,'by_week'=>$weeks,'as_of'=>$asOf,'page'=>$page,'total_pages'=>max(1,(int)ceil($total/50))];
    }
    public function directory(array $f,bool $export=false): array {
        if($f['mode']==='users'){
            $fields=['id'=>'ID','fullname'=>'Họ tên','username'=>'Username','email'=>'Email','tel'=>'Điện thoại','department_name'=>'Phòng ban','status'=>'Trạng thái'];
            $select='u.id,u.fullname,u.username,u.email,u.tel,d.name department_name,u.status';
            $join=' FROM dc_users u LEFT JOIN dc_pm_departments d ON d.store_id=u.store_id AND d.id=u.department_id WHERE u.store_id=? AND u.status<>2';$types='i';$params=[$f['store_id']];
            if($f['role_code']!==''){$join.=' AND EXISTS(SELECT 1 FROM dc_pm_user_roles ur JOIN dc_pm_roles r ON r.store_id=ur.store_id AND r.id=ur.role_id WHERE ur.store_id=u.store_id AND ur.user_id=u.id AND r.status=1 AND r.code=?)';$types.='s';$params[]=$f['role_code'];}
            if($f['user_id']!==null){$join.=' AND u.id=?';$types.='i';$params[]=$f['user_id'];}
            $order='u.fullname,u.id';
        }else{
            $fields=['id'=>'ID','code'=>'Mã','name'=>'Dự án','client_name'=>'Khách hàng','manager_name'=>'Người phụ trách','start_date'=>'Bắt đầu','end_date'=>'Kết thúc','status'=>'Trạng thái'];
            $select='p.id,p.code,p.name,p.client_name,u.fullname manager_name,p.start_date,p.end_date,p.status';$join=' FROM dc_pm_projects p LEFT JOIN dc_users u ON u.store_id=p.store_id AND u.id=p.manager_id WHERE p.store_id=? AND p.deleted_at IS NULL';$types='i';$params=[$f['store_id']];
            if($f['scope']==='pm'){$join.=' AND p.manager_id=?';$types.='i';$params[]=$f['actor_id'];}
            elseif($f['scope']==='own'){$join.=' AND EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=p.store_id AND m.project_id=p.id AND m.user_id=? AND m.status=1)';$types.='i';$params[]=$f['actor_id'];}
            if($f['project_id']!==null){$join.=' AND p.id=?';$types.='i';$params[]=$f['project_id'];}
            $join.=' AND (p.start_date IS NULL OR p.start_date<=?) AND (p.end_date IS NULL OR p.end_date>=?)';$types.='ss';array_push($params,$f['to'],$f['from']);$order='p.id';
        }
        $total=(int)$this->db->fetchOne('SELECT COUNT(*) total'.$join,$types,$params)['total'];
        if($export&&$total>5000)throw new InvalidArgumentException('Export tối đa 5000 dòng.');$page=min($f['page'],max(1,(int)ceil($total/50)));
        $rows=$this->db->fetchAll('SELECT '.$select.$join.' ORDER BY '.$order.' LIMIT ? OFFSET ?',$types.'ii',[...$params,$export?5000:50,$export?0:($page-1)*50]);
        return ['rows'=>$rows,'fields'=>$fields,'page'=>$page,'total_pages'=>max(1,(int)ceil($total/50))];
    }
}
