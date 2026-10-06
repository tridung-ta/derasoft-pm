<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

class PmAuth {
    private PmDb $database;

    public function __construct($database) {
        $this->database = new PmDb($database);
    }

    public function findUserIdByIdentity(int $storeId, string $identity): int {
        $user = $this->findUser($storeId, $identity);
        return $user ? (int) $user['id'] : 0;
    }

    /** Returns user id, 0 for invalid credentials, or -1 for a disabled account. */
    public function authenticate(int $storeId, string $identity, string $password): int {
        if ($identity === '' || $password === '') {
            return 0;
        }
        $user = $this->findUser($storeId, $identity);
        if (!$user) {
            return 0;
        }
        $modernHash = (string) ($user['password_hash'] ?? '');
        $verified = $modernHash !== '' && password_verify($password, $modernHash);
        $legacyVerified = false;
        if (!$verified && $modernHash === '') {
            $legacyHash = (string) ($user['password'] ?? '');
            $legacyVerified = strlen($legacyHash) === 32 && hash_equals(strtolower($legacyHash), md5($password));
        }
        if (!$verified && !$legacyVerified) {
            return 0;
        }
        if ((int) $user['status'] !== 1) {
            return -1;
        }

        $newHash = null;
        if ($legacyVerified || password_needs_rehash($modernHash, PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($newHash !== null) {
            $this->database->execute(
                'UPDATE dc_users SET password_hash = ?, last_login = NOW() WHERE store_id = ? AND id = ?',
                'sii',
                [$newHash, $storeId, (int) $user['id']]
            );
        } else {
            $this->database->execute(
                'UPDATE dc_users SET last_login = NOW() WHERE store_id = ? AND id = ?',
                'ii',
                [$storeId, (int) $user['id']]
            );
        }
        return (int) $user['id'];
    }

    public function changePassword(int $storeId, int $userId, string $newPassword): bool {
        if (strlen($newPassword) < 8) {
            return false;
        }
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->database->execute(
            'UPDATE dc_users SET password_hash = ? WHERE store_id = ? AND id = ?',
            'sii',
            [$hash, $storeId, $userId]
        ) === 1;
    }

    private function findUser(int $storeId, string $identity): ?array {
        $identity = trim($identity);
        // Both identifiers address one credential row. Never choose another
        // account's username ahead of this account's email on a collision.
        $matches = $this->database->fetchAll(
            'SELECT id, password, password_hash, status FROM dc_users
             WHERE store_id = ? AND (username = ? OR email = ?) LIMIT 2',
            'iss',
            [$storeId, $identity, $identity]
        );
        return count($matches) === 1 ? $matches[0] : null;
    }
}
