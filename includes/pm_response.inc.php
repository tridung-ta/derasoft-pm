<?php
/** PM-only headers; legacy response policy is unchanged. */
function pmResponseHeaders(): void {
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: same-origin');
    header('X-Frame-Options: SAMEORIGIN');
    // Inventory + pre-policy browser checks documented in PHASE9_CSP_INVENTORY.md.
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'");
}
