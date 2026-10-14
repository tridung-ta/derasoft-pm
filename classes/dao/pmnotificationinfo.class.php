<?php
class PmNotificationInfo {
    public function __construct(private array $row) {}
    public function toArray(): array {return $this->row;}
}
