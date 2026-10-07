<?php
/** Presentation only: endpoint authorization remains in controllers/services. */
class PmUiService {
    public static function roleState(array $grouped): array {
        $state=[];$primary=[];$inconsistent=[];
        foreach($grouped as $userId=>$roles){
            $matches=[];foreach($roles as $role){$state[$userId][(int)$role['role_id']]=true;if((int)$role['is_primary']===1)$matches[]=(int)$role['role_id'];}
            if(count($matches)===1)$primary[$userId]=$matches[0];
            elseif(count($matches)>1)$inconsistent[]=(int)$userId;
        }
        return ['state'=>$state,'primary'=>$primary,'inconsistent'=>$inconsistent];
    }
    public static function navigation($access,string $op,string $act=''): array {
        $items=[];
        $add=static function(string $route,string $label,bool $allowed,string $section='') use (&$items,$op,$act): void {
            if($allowed)$items[]=['label'=>$label,'url'=>'?op='.$route.($section!==''?'&act='.$section:''),'current'=>$op===$route&&($section!==''?$act===$section:($route!=='pm'||$act===''||$act==='dashboard'))];
        };
        $admin=$access->hasRole('ADMIN');$pm=$access->hasRole('PM');$hr=$access->hasRole('HR');$employee=$access->hasRole('EMPLOYEE');
        $add('pm','Tổng quan',$access->hasPermission('pm.dashboard.view'));
        $add('pm','Hồ sơ',$access->hasPermission('pm.profile.view'),'profile');
        $add('pm','Đổi mật khẩu',$access->hasPermission('pm.password.update'),'password');
        $add('pm','Danh sách nhóm',$access->hasPermission('pm.team.view'),'team');
        $add('pmusers','Nhân sự',$access->hasPermission('pm.team.view'));
        $add('pmprojects','Dự án & công việc',$access->hasPermission('pm.projects.view'));
        $add('pmtimesheets','Chấm công',$access->hasPermission('pm.timesheets.own'));
        $add('pmaudit','Nhật ký',$access->hasPermission('pm.audit.view')&&($admin||$pm||$hr));
        $add('pmcosts','Chi phí',$access->hasPermission('pm.costs.view')&&($admin||$pm));
        $add('pmallocations','Phân bổ',$access->hasPermission('pm.allocations.view')&&($admin||$pm||$employee));
        $add('pmreports','Báo cáo',$access->hasPermission('pm.reports.view'));
        $add('pmimports','Import nhân sự',$access->hasPermission('pm.imports.manage')&&$admin);
        return $items;
    }
}
