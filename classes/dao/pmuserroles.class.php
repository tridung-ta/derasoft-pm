<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

class PmUserRoles {
    private PmDb $database;

    public function __construct($database) {
        $this->database = new PmDb($database);
    }

    public function assign(int $storeId, int $userId, int $roleId, bool $primary = false): bool {
        $affected = $this->database->execute(
            'INSERT INTO dc_pm_user_roles (store_id, user_id, role_id, is_primary)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE is_primary = VALUES(is_primary)',
            'iiii',
            [$storeId, $userId, $roleId, $primary ? 1 : 0]
        );
        return $affected >= 0;
    }
}
