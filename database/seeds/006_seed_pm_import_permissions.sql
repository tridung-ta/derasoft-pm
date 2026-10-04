INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.imports.manage','Kiểm tra và staging nhân sự','imports','Admin-only, chưa áp dụng nhân sự' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_role_permissions(store_id,role_id,permission_id)
SELECT r.store_id,r.id,p.id FROM dc_pm_roles r JOIN dc_pm_permissions p ON p.store_id=r.store_id
WHERE r.status=1 AND r.code='ADMIN' AND p.code='pm.imports.manage';
