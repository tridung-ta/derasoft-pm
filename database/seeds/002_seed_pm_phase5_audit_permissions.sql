-- Phase 5: additive tenant-scoped audit viewer grants for existing system roles.
-- Prerequisites: migrations 001-004, local/staging backup. No production execution during development.
-- Idempotent. Legacy approval permission remains unused; no workflow is implemented.
-- Rollback application: disable audit route/menu, retain permission mappings without deleting data.
INSERT IGNORE INTO dc_pm_role_permissions(store_id,role_id,permission_id)
SELECT r.store_id,r.id,p.id FROM dc_pm_roles r
JOIN dc_pm_permissions p ON p.store_id=r.store_id AND p.code='pm.audit.view'
WHERE r.code IN ('ADMIN','PM','HR') AND r.status=1;
