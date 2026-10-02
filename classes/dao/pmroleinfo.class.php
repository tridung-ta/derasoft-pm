<?php
class PmRoleInfo {
    public function __construct(
        private int $id,
        private int $storeId,
        private string $code,
        private string $name,
        private bool $isSystem,
        private bool $active
    ) {}

    public function getId(): int { return $this->id; }
    public function getStoreId(): int { return $this->storeId; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function isSystem(): bool { return $this->isSystem; }
    public function isActive(): bool { return $this->active; }
}
