<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/dao/pmroles.class.php');
include_once(ROOT_PATH.'classes/dao/pmpermissions.class.php');

class PmAccess {
    private PmDb $database;
    private array $roleCodes;
    private array $permissionCodes;

    public function __construct($database, private int $storeId, private int $userId) {
        $this->database = new PmDb($database);
        $roles = (new PmRoles($database))->getUserRoles($storeId, $userId);
        $this->roleCodes = array_map(fn(PmRoleInfo $role) => $role->getCode(), $roles);
        $this->permissionCodes = (new PmPermissions($database))->getUserPermissionCodes($storeId, $userId);
    }

    public function hasRole(string $roleCode): bool {
        return in_array(strtoupper($roleCode), $this->roleCodes, true);
    }

    public function hasPermission(string $permissionCode): bool {
        return $this->hasRole('ADMIN') || in_array($permissionCode, $this->permissionCodes, true);
    }

    public function hasProjectAccess(int $projectId): bool {
        if ($projectId <= 0) {
            return false;
        }
        // Project tables are introduced in their own phase. Fail closed until then.
        $table = $this->database->fetchOne(
            "SELECT COUNT(*) AS total FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name IN ('dc_pm_projects','dc_pm_project_members')"
        );
        if (!$table || (int) $table['total'] !== 2) {
            return false;
        }
        $project=$this->database->fetchOne('SELECT manager_id FROM dc_pm_projects WHERE store_id=? AND id=? AND deleted_at IS NULL','ii',[$this->storeId,$projectId]);
        if(!$project)return false;
        if($this->hasRole('ADMIN')||($this->hasRole('PM')&&(int)$project['manager_id']===$this->userId))return true;
        return $this->database->fetchOne(
            'SELECT 1 FROM dc_pm_project_members
             WHERE store_id = ? AND project_id = ? AND user_id = ? AND status = 1 LIMIT 1',
            'iii',
            [$this->storeId, $projectId, $this->userId]
        ) !== null;
    }

    public function getRoleCodes(): array { return $this->roleCodes; }
    public function getPermissionCodes(): array { return $this->permissionCodes; }
}

function requirePermission(string $permissionCode): void {
    global $pmAccess;
    if (!isset($pmAccess) || !$pmAccess instanceof PmAccess || !$pmAccess->hasPermission($permissionCode)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function requireProjectAccess(int $projectId): void {
    global $pmAccess;
    if (!isset($pmAccess) || !$pmAccess instanceof PmAccess || !$pmAccess->hasProjectAccess($projectId)) {
        http_response_code(403);
        exit('Forbidden');
    }
}
