<?php
class PmPermissionInfo {
    public function __construct(
        private int $id,
        private string $code,
        private string $name,
        private string $module
    ) {}

    public function getId(): int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getName(): string { return $this->name; }
    public function getModule(): string { return $this->module; }
}
