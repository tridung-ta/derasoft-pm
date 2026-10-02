<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/dao/pmpermissioninfo.class.php');

class PmPermissions {
    private PmDb $database;

    public function __construct($database) {
        $this->database = new PmDb($database);
    }

    public function getUserPermissionCodes(int $storeId, int $userId): array {
        $rows = $this->database->fetchAll(
            'SELECT DISTINCT p.code
             FROM dc_pm_permissions p
             INNER JOIN dc_pm_role_permissions rp ON rp.permission_id = p.id
             INNER JOIN dc_pm_user_roles ur ON ur.role_id = rp.role_id
             INNER JOIN dc_pm_roles r ON r.id = ur.role_id AND r.store_id = ur.store_id
             WHERE ur.store_id = ? AND ur.user_id = ? AND r.status = 1
             ORDER BY p.code ASC',
            'ii',
            [$storeId, $userId]
        );
        return array_column($rows, 'code');
    }

    public function getByCode(string $code): ?PmPermissionInfo {
        $row = $this->database->fetchOne(
            'SELECT id, code, name, module FROM dc_pm_permissions WHERE code = ?',
            's',
            [$code]
        );
        return $row ? new PmPermissionInfo(
            (int) $row['id'],
            (string) $row['code'],
            (string) $row['name'],
            (string) $row['module']
        ) : null;
    }
}
