<?php
include_once(ROOT_PATH.'classes/dao/pmdashboard.class.php');
include_once(ROOT_PATH.'classes/services/pmcostservice.class.php');
include_once(ROOT_PATH.'classes/services/pmallocationservice.class.php');

/** Read-only orchestration; permission gates precede every provider/query family. */
class PmDashboardService {
    public static function overview($database,$access,int $storeId,int $actorId): array {
        return self::compose(
            new PmDashboard(new PmDb($database),$storeId),$access,$actorId,
            new DateTimeImmutable('today',new DateTimeZone('Asia/Ho_Chi_Minh')),
            fn()=>(new PmCostService($database,$storeId,$actorId))->dashboard(),
            fn(string $week)=>(new PmAllocationService($database,$storeId,$actorId))->dashboardSummary($week)
        );
    }

    public static function compose(PmDashboard $dao,$access,int $actorId,DateTimeImmutable $today,
        callable $costProvider,callable $allocationProvider): array {
        $date=$today->format('Y-m-d');
        $start=$today->modify('-'.((int)$today->format('N')-1).' days')->format('Y-m-d');
        $out=['today_hours'=>null,'week_hours'=>null,'task_count'=>null,'due_tasks'=>[],
            'needs_timesheet'=>false,'errors'=>[],'management'=>null];
        if ($access->hasPermission('pm.timesheets.own')) {
            try {
                $hours=$dao->ownHours($actorId,$date,$start);
                $out['today_hours']=(string)$hours['today_hours'];$out['week_hours']=(string)$hours['week_hours'];
                $out['needs_timesheet']=(int)$hours['today_entries']===0;
            } catch(Throwable $e) {self::unavailable($out,'Không thể tải giờ chấm công.','hours');}
        }
        if ($access->hasPermission('pm.projects.view') && $access->hasPermission('pm.tasks.view')) {
            try {
                $tasks=$dao->ownTasks($actorId,$today->modify('+7 days')->format('Y-m-d'));
                $out['task_count']=$tasks['count'];$out['due_tasks']=$tasks['due'];
            } catch(Throwable $e) {self::unavailable($out,'Không thể tải công việc được giao.','tasks');}
        }
        if (!($access->hasRole('ADMIN')||$access->hasRole('PM'))) return $out;
        $management=['late_projects'=>null,'budget_projects'=>null,'budget_more'=>false,
            'allocation_alerts'=>null,'allocation_truncated'=>false,'task_estimate_warnings'=>[],
            'missing_rates'=>null,'week'=>$start];
        if ($access->hasPermission('pm.projects.view') && $access->hasPermission('pm.tasks.view')) {
            try {$management['late_projects']=$dao->lateProjects($access->hasRole('ADMIN')?null:$actorId,$date);}
            catch(Throwable $e) {self::unavailable($out,'Không thể tải dự án có công việc trễ.','projects');}
        }
        if ($access->hasPermission('pm.costs.view')) {
            try {
                $costs=$costProvider();$management['budget_projects']=[];
                $management['missing_rates']=(int)($costs['summary']['missing_rate_entries']??0);
                $management['budget_more']=($costs['total_pages']??1)>1;
                foreach($costs['projects'] as $project) {
                    $actual=$project['lifetime'];$percent=null;$reason=null;
                    if (preg_match('/^0+(?:\.0+)?$/D',(string)$project['budget'])) $reason='Chưa có ngân sách';
                    elseif (($actual['missing_rate_entries']??0)>0) $reason='Chi phí chưa đầy đủ đơn giá';
                    elseif ($actual['cost']===null || $actual['currency']!=='VND') $reason='Chi phí không so sánh được với ngân sách VND';
                    else {
                        $percent=$dao->budgetPercent((string)$actual['cost'],(string)$project['budget']);
                        if ($percent===null) $reason='Chưa tính được tỷ lệ ngân sách';
                    }
                    $management['budget_projects'][]=['id'=>$project['id'],'name'=>$project['name'],
                        'budget'=>(string)$project['budget'],'actual_cost'=>$actual['cost'],
                        'percentage'=>$percent,'reason'=>$reason];
                }
            } catch(Throwable $e) {
                $management['budget_projects']=null;$management['missing_rates']=null;
                self::unavailable($out,'Không thể tải tình hình ngân sách.','costs');
            }
        }
        if ($access->hasPermission('pm.allocations.view')) {
            try {
                $allocation=$allocationProvider($start);$alerts=[];
                foreach($allocation['rows'] as $row) {
                    if (!$row['warnings']) continue;
                    $key=$row['user_id'].'/'.$row['work_date'];
                    $alerts[$key]=['date'=>$row['work_date'],'name'=>$row['user_name'],'warnings'=>$row['warnings']];
                }
                $management['allocation_alerts']=array_slice(array_values($alerts),0,8);
                $management['allocation_truncated']=!empty($allocation['truncated'])||count($alerts)>8;
                $management['task_estimate_warnings']=array_slice($allocation['task_estimate_warnings']??[],0,8);
            } catch(Throwable $e) {self::unavailable($out,'Không thể tải cảnh báo phân bổ tuần này.','allocations');}
        }
        $out['management']=$management;return $out;
    }

    private static function unavailable(array &$out,string $message,string $family): void {
        $out['errors'][]=$message;
        // No exception text, SQL, personal data or credentials in logs.
        error_log('PM dashboard '.$family.' unavailable.');
    }
}
