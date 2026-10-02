<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
include_once(ROOT_PATH.'classes/dao/pmroleinfo.class.php');

class PmRoles {
    private PmDb $database;

    public function __construct($database) {
        $this->database = new PmDb($database);
    }

    public function getUserRoles(int $storeId, int $userId): array {
        $rows = $this->database->fetchAll(
            'SELECT r.id, r.store_id, r.code, r.name, r.is_system, r.status
             FROM dc_pm_roles r
             INNER JOIN dc_pm_user_roles ur ON ur.role_id = r.id AND ur.store_id = r.store_id
             WHERE ur.store_id = ? AND ur.user_id = ? AND r.status = 1
             ORDER BY ur.is_primary DESC, r.code ASC',
            'ii',
            [$storeId, $userId]
        );
        return array_map([$this, 'hydrate'], $rows);
    }

    public function getByCode(int $storeId, string $code): ?PmRoleInfo {
        $row = $this->database->fetchOne(
            'SELECT id, store_id, code, name, is_system, status
             FROM dc_pm_roles WHERE store_id = ? AND code = ? AND status = 1',
            'is',
            [$storeId, strtoupper($code)]
        );
        return $row ? $this->hydrate($row) : null;
    }

    public function getActive(int $storeId): array {
        return $this->database->fetchAll('SELECT id,code,name FROM dc_pm_roles WHERE store_id=? AND status=1 ORDER BY name','i',[$storeId]);
    }

    private function hydrate(array $row): PmRoleInfo {
        return new PmRoleInfo(
            (int) $row['id'],
            (int) $row['store_id'],
            (string) $row['code'],
            (string) $row['name'],
            (bool) $row['is_system'],
            (bool) $row['status']
        );
    }
}
