<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

class PmRolePermissions {
    private PmDb $database;

    public function __construct($database) {
        $this->database = new PmDb($database);
    }

    public function roleHasPermission(int $roleId, string $permissionCode): bool {
        return $this->database->fetchOne(
            'SELECT 1 FROM dc_pm_role_permissions rp
             INNER JOIN dc_pm_permissions p ON p.id = rp.permission_id
             WHERE rp.role_id = ? AND p.code = ? LIMIT 1',
            'is',
            [$roleId, $permissionCode]
        ) !== null;
    }
}
