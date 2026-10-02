<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/security/pmaccess.class.php');

class PmAuditLogs {
    private PmDb $db;
    private PmAccess $access;

    public function __construct($database, private int $storeId, private int $actorId) {
        $this->db = new PmDb($database);
        $this->access = new PmAccess($database, $storeId, $actorId);
    }

    public function list(array $filters = [], int $page = 1): array {
        if (!$this->access->hasPermission('pm.audit.view') || !($this->access->hasRole('ADMIN') || $this->access->hasRole('PM') || $this->access->hasRole('HR'))) {
            throw new DomainException('Bạn không có quyền xem nhật ký kiểm toán.');
        }
        $where = 'a.store_id=?'; $types = 'i'; $params = [$this->storeId];
        if (!$this->access->hasRole('ADMIN')) {
            $where .= " AND a.entity_type='timesheet'";
            $scopes = [];
            if ($this->access->hasRole('PM')) {
                // Both snapshots must be in scope: moving a row must not reveal an unrelated project.
                $projectScope = [];
                foreach (['old_values', 'new_values'] as $column) {
                    $projectScope[] = "(a.$column IS NULL OR EXISTS(SELECT 1 FROM dc_pm_projects p WHERE p.store_id=a.store_id AND p.id=CAST(JSON_UNQUOTE(JSON_EXTRACT(a.$column,'$.project_id')) AS UNSIGNED) AND p.manager_id=?))";
                    $types .= 'i'; $params[] = $this->actorId;
                }
                $scopes[] = '('.implode(' AND ', $projectScope).')';
            }
            if ($this->access->hasRole('HR')) {
                // Provisional HR read scope: own active department, otherwise personal history.
                $scopes[] = "(CAST(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(a.new_values,a.old_values),'$.user_id')) AS UNSIGNED)=? OR EXISTS(SELECT 1 FROM dc_users owner JOIN dc_users viewer ON viewer.store_id=owner.store_id AND viewer.department_id=owner.department_id JOIN dc_pm_departments d ON d.store_id=viewer.store_id AND d.id=viewer.department_id AND d.status=1 AND d.deleted_at IS NULL WHERE owner.store_id=a.store_id AND owner.id=CAST(JSON_UNQUOTE(JSON_EXTRACT(COALESCE(a.new_values,a.old_values),'$.user_id')) AS UNSIGNED) AND viewer.id=?))";
                $types .= 'ii'; $params[] = $this->actorId; $params[] = $this->actorId;
            }
            $where .= ' AND ('.implode(' OR ', $scopes).')';
        }
        foreach (['entity_type', 'action'] as $key) {
            $value = (string)($filters[$key] ?? '');
            if ($value === '') continue;
            $allowed = $key === 'entity_type' ? ['timesheet', 'settings'] : ['create', 'update', 'soft_delete', 'recalculate', 'admin_update_locked', 'admin_delete_locked', 'admin_create_locked'];
            if (!in_array($value, $allowed, true)) throw new InvalidArgumentException('Bộ lọc không hợp lệ.');
            $where .= ' AND a.'.$key.'=?'; $types .= 's'; $params[] = $value;
        }
        $total = (int)$this->db->fetchOne('SELECT COUNT(*) total FROM dc_pm_audit_logs a WHERE '.$where, $types, $params)['total'];
        $page = min(max(1, $page), max(1, (int)ceil($total / 20)));
        $rows = $this->db->fetchAll('SELECT a.* FROM dc_pm_audit_logs a WHERE '.$where.' ORDER BY a.id DESC LIMIT 20 OFFSET ?', $types.'i', [...$params, ($page - 1) * 20]);
        if (!$this->access->hasRole('ADMIN')) {
            $visibleFields = array_flip(['id','store_id','user_id','project_id','task_id','work_date','shift_label','hours','regular_hours','ot_hours','description','deleted_at','created_at','updated_at']);
            foreach ($rows as &$row) {
                foreach (['old_values','new_values'] as $column) {
                    if ($row[$column] !== null) {
                        $values = json_decode($row[$column], true, 512, JSON_THROW_ON_ERROR);
                        $row[$column] = json_encode(array_intersect_key($values, $visibleFields), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                    }
                }
            }
            unset($row);
        }
        return ['rows' => $rows, 'total' => $total, 'page' => $page];
    }
}
