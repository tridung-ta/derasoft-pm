-- Additive tenant grants; HR reports remain personal, without financial data.
INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.reports.view','Xem báo cáo','reports','Đọc báo cáo theo role và phạm vi' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.reports.export','Xuất báo cáo','reports','Xuất XLSX theo phạm vi đọc' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_role_permissions(store_id,role_id,permission_id)
SELECT r.store_id,r.id,p.id FROM dc_pm_roles r JOIN dc_pm_permissions p ON p.store_id=r.store_id
WHERE r.status=1 AND r.code IN ('ADMIN','PM','HR','EMPLOYEE') AND p.code IN ('pm.reports.view','pm.reports.export');
-- Rollback: hide feature; retain permission/history.
