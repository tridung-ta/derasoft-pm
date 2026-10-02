<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');

class PmRateService {
    private PmDb $database;

    public function __construct($database) { $this->database = new PmDb($database); }

    public function validateRateOwner(?int $userId, ?int $roleId): void {
        $hasUser = $userId !== null && $userId > 0;
        $hasRole = $roleId !== null && $roleId > 0;
        if ($hasUser === $hasRole) {
            throw new InvalidArgumentException('Hourly rate must belong to exactly one user or role.');
        }
    }

    public function saveRate(int $storeId, array $data, int $actorId, ?int $rateId = null): int {
        $userId = isset($data['user_id']) && $data['user_id'] !== '' ? (int) $data['user_id'] : null;
        $roleId = isset($data['role_id']) && $data['role_id'] !== '' ? (int) $data['role_id'] : null;
        $from = (string) ($data['effective_from'] ?? '');
        $to = !empty($data['effective_to']) ? (string) $data['effective_to'] : null;
        $rate = (float) ($data['rate'] ?? -1);
        $currency = strtoupper((string) ($data['currency'] ?? 'VND'));
        $this->validateRateOwner($userId, $roleId);
        $this->validateDates($from, $to);
        if ($rate < 0 || !preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Invalid hourly rate or currency.');
        }
        if ($userId !== null && !$this->database->fetchOne('SELECT 1 FROM dc_users WHERE store_id=? AND id=? AND status<>2','ii',[$storeId,$userId])) {
            throw new OutOfBoundsException('Rate user was not found in this tenant.');
        }
        if ($roleId !== null && !$this->database->fetchOne('SELECT 1 FROM dc_pm_roles WHERE store_id=? AND id=? AND status=1','ii',[$storeId,$roleId])) {
            throw new OutOfBoundsException('Rate role was not found in this tenant.');
        }

        $this->database->beginTransaction();
        try {
            $this->lockOwnerRates($storeId, $userId, $roleId);
            if ($this->hasOverlappingRate($storeId, $userId, $roleId, $from, $to, $rateId)) {
                throw new DomainException('Hourly rate effective period overlaps an existing rate.');
            }
            if ($rateId === null) {
                $this->database->execute(
                    'INSERT INTO dc_pm_hourly_rates (store_id,user_id,role_id,rate,currency,effective_from,effective_to,status,created_by)
                     VALUES (?,?,?,?,?,?,?,1,?)',
                    'iiidsssi', [$storeId, $userId, $roleId, $rate, $currency, $from, $to, $actorId]
                );
                $saved = $this->database->fetchOne('SELECT LAST_INSERT_ID() AS id');
                $savedId = (int) $saved['id'];
            } else {
                $exists = $this->database->fetchOne(
                    'SELECT id FROM dc_pm_hourly_rates WHERE store_id = ? AND id = ? AND status = 1 FOR UPDATE',
                    'ii', [$storeId, $rateId]
                );
                if (!$exists) throw new OutOfBoundsException('Hourly rate was not found in this tenant.');
                $this->database->execute(
                    'UPDATE dc_pm_hourly_rates SET user_id=?,role_id=?,rate=?,currency=?,effective_from=?,effective_to=?
                     WHERE store_id=? AND id=? AND status=1',
                    'iidsssii', [$userId, $roleId, $rate, $currency, $from, $to, $storeId, $rateId]
                );
                $savedId = $rateId;
            }
            $this->database->commit();
            return $savedId;
        } catch (Throwable $error) {
            $this->database->rollBack();
            throw $error;
        }
    }

    public function hasOverlappingRate(int $storeId, ?int $userId, ?int $roleId, string $from, ?string $to, ?int $excludeId = null): bool {
        $this->validateRateOwner($userId, $roleId);
        $ownerColumn = $userId !== null ? 'user_id' : 'role_id';
        $ownerId = $userId ?? $roleId;
        $sql = "SELECT 1 FROM dc_pm_hourly_rates WHERE store_id=? AND {$ownerColumn}=? AND status=1
                AND (effective_to IS NULL OR effective_to >= ?)
                AND (? IS NULL OR ? >= effective_from)";
        $types = 'iisss';
        $params = [$storeId, $ownerId, $from, $to, $to];
        if ($excludeId !== null) { $sql .= ' AND id <> ?'; $types .= 'i'; $params[] = $excludeId; }
        return $this->database->fetchOne($sql.' LIMIT 1', $types, $params) !== null;
    }

    public function resolveRate(int $storeId, int $userId, string $workDate): array {
        $rate = $this->database->fetchOne(
            'SELECT id,rate,currency FROM dc_pm_hourly_rates WHERE store_id=? AND user_id=? AND status=1
             AND effective_from<=? AND (effective_to IS NULL OR effective_to>=?) ORDER BY effective_from DESC,id DESC LIMIT 1',
            'iiss', [$storeId, $userId, $workDate, $workDate]
        );
        if ($rate) return $this->rateResult($rate, 'user', null);
        $rate = $this->database->fetchOne(
            'SELECT hr.id,hr.rate,hr.currency FROM dc_pm_hourly_rates hr
             INNER JOIN dc_pm_user_roles ur ON ur.store_id=hr.store_id AND ur.role_id=hr.role_id
             WHERE hr.store_id=? AND ur.user_id=? AND ur.is_primary=1 AND hr.status=1
               AND hr.effective_from<=? AND (hr.effective_to IS NULL OR hr.effective_to>=?)
             ORDER BY hr.effective_from DESC,hr.id DESC LIMIT 1',
            'iiss', [$storeId, $userId, $workDate, $workDate]
        );
        if ($rate) return $this->rateResult($rate, 'role', null);
        return ['rate'=>0.0,'currency'=>'VND','source'=>'fallback','source_id'=>null,
            'warning'=>'Hourly rate is not configured for this user and date.'];
    }

    private function lockOwnerRates(int $storeId, ?int $userId, ?int $roleId): void {
        $ownerColumn = $userId !== null ? 'user_id' : 'role_id';
        $ownerId = $userId ?? $roleId;
        $this->database->fetchAll(
            "SELECT id FROM dc_pm_hourly_rates WHERE store_id=? AND {$ownerColumn}=? AND status=1 FOR UPDATE",
            'ii', [$storeId, $ownerId]
        );
    }

    private function validateDates(string $from, ?string $to): void {
        $fromDate = DateTimeImmutable::createFromFormat('!Y-m-d', $from);
        $toDate = $to !== null ? DateTimeImmutable::createFromFormat('!Y-m-d', $to) : null;
        if (!$fromDate || $fromDate->format('Y-m-d') !== $from
            || ($to !== null && (!$toDate || $toDate->format('Y-m-d') !== $to))
            || ($toDate && $toDate < $fromDate)) {
            throw new InvalidArgumentException('Invalid hourly rate effective period.');
        }
    }

    private function rateResult(array $row, string $source, ?string $warning): array {
        return ['rate'=>(float)$row['rate'],'currency'=>(string)$row['currency'],'source'=>$source,
            'source_id'=>(int)$row['id'],'warning'=>$warning];
    }
}
