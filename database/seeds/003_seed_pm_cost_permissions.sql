-- Phase 6 approved: additive, tenant-scoped, idempotent cost-view permission.
-- Prerequisites: migrations 001-004; verified local/staging backup.
-- Rollback application: hide route/menu, retain unused permission/mappings.
INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.costs.view','Xem chi phí','costs','Xem chi phí trong phạm vi dự án được quản lý' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_role_permissions(store_id,role_id,permission_id)
SELECT r.store_id,r.id,p.id FROM dc_pm_roles r
JOIN dc_pm_permissions p ON p.store_id=r.store_id AND p.code='pm.costs.view'
WHERE r.code IN ('ADMIN','PM') AND r.status=1;
