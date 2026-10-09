<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

/** Prepared, read-only overview queries. No input can override the tenant. */
class PmDashboard {
    public function __construct(private PmDb $db, private int $storeId) {}

    public function ownHours(int $actorId, string $date, string $start): array {
        return $this->db->fetchOne(
            'SELECT COALESCE(SUM(CASE WHEN work_date=? THEN hours ELSE 0 END),0) today_hours,
             COALESCE(SUM(hours),0) week_hours,COUNT(CASE WHEN work_date=? THEN id END) today_entries
             FROM dc_pm_timesheets WHERE store_id=? AND user_id=? AND work_date BETWEEN ? AND ? AND deleted_at IS NULL',
            'ssiiss', [$date,$date,$this->storeId,$actorId,$start,$date]
        ) ?? ['today_hours'=>'0.00','week_hours'=>'0.00','today_entries'=>0];
    }

    public function ownTasks(int $actorId, string $end): array {
        // EXISTS avoids multiplying a task if legacy membership contains duplicate rows.
        $scope=' FROM dc_pm_tasks t JOIN dc_pm_projects p ON p.store_id=t.store_id AND p.id=t.project_id
            WHERE t.store_id=? AND t.assignee_id=? AND t.deleted_at IS NULL AND p.deleted_at IS NULL AND t.status<>?
            AND EXISTS(SELECT 1 FROM dc_pm_project_members m WHERE m.store_id=t.store_id
                AND m.project_id=t.project_id AND m.user_id=t.assignee_id AND m.status=1)';
        return [
            'count'=>(int)($this->db->fetchOne('SELECT COUNT(*) n'.$scope,'iis',[$this->storeId,$actorId,'done'])['n']??0),
            'due'=>$this->db->fetchAll('SELECT t.id,t.name,t.project_id,t.due_date,p.name project_name'.$scope.
                ' AND t.due_date IS NOT NULL AND t.due_date<=? ORDER BY t.due_date,t.id LIMIT 8',
                'iiss',[$this->storeId,$actorId,'done',$end])
        ];
    }

    public function lateProjects(?int $managerId, string $date): array {
        $scope=$managerId===null?'':' AND p.manager_id=?';
        return $this->db->fetchAll(
            'SELECT p.id,p.name,COUNT(*) late_tasks,MIN(t.due_date) oldest_due
             FROM dc_pm_projects p JOIN dc_pm_tasks t ON t.store_id=p.store_id AND t.project_id=p.id
             WHERE p.store_id=? AND p.deleted_at IS NULL AND t.deleted_at IS NULL
             AND t.status<>? AND t.due_date<?'.$scope.
             ' GROUP BY p.id,p.name ORDER BY oldest_due,p.id LIMIT 8',
            'iss'.($managerId===null?'':'i'),$managerId===null?[$this->storeId,'done',$date]:[$this->storeId,'done',$date,$managerId]
        );
    }

    public function budgetPercent(string $cost, string $budget): ?string {
        foreach ([$cost,$budget] as $value) {
            if (!preg_match('/^\d{1,28}(?:\.\d{1,2})?$/D',$value)) return null;
        }
        // Decimal arithmetic stays in MySQL; no PHP/JS float conversion for money.
        $row=$this->db->fetchOne('SELECT CAST(CAST(? AS DECIMAL(30,2))*100 /
            NULLIF(CAST(? AS DECIMAL(30,2)),0) AS DECIMAL(34,2)) percentage','ss',[$cost,$budget]);
        return ($row['percentage']??null)===null?null:(string)$row['percentage'];
    }
}
