<?php
/** Typed authorization denial; ordinary business DomainException remains distinct. */
class PmScopeDeniedException extends DomainException {
    public const MESSAGE='Không thể truy cập dữ liệu.';
}
