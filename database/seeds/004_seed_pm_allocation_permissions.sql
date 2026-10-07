-- Additive tenant-scoped grants. Rollback: hide feature, retain grants.
INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.allocations.view','Xem phân bổ','allocations','Đọc lịch phân bổ theo phạm vi' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_permissions(store_id,code,name,module,description)
SELECT DISTINCT store_id,'pm.allocations.manage','Quản lý phân bổ','allocations','Ghi phân bổ theo phạm vi dự án' FROM dc_pm_roles;
INSERT IGNORE INTO dc_pm_role_permissions(store_id,role_id,permission_id)
SELECT r.store_id,r.id,p.id FROM dc_pm_roles r
JOIN dc_pm_permissions p ON p.store_id=r.store_id
WHERE r.status=1 AND ((p.code='pm.allocations.view' AND r.code IN ('ADMIN','PM','EMPLOYEE'))
 OR (p.code='pm.allocations.manage' AND r.code IN ('ADMIN','PM')));
