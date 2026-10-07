-- Phase 2 idempotent system-role and permission seed.
-- Run only after database/migrations/001_create_pm_auth_rbac.sql.

INSERT INTO `dc_pm_permissions` (`code`, `name`, `module`, `description`) VALUES
('pm.dashboard.view', 'Xem tổng quan', 'dashboard', 'Truy cập tổng quan PM'),
('pm.profile.view', 'Xem hồ sơ cá nhân', 'profile', 'Xem hồ sơ của chính mình'),
('pm.profile.update', 'Cập nhật hồ sơ cá nhân', 'profile', 'Cập nhật hồ sơ của chính mình'),
('pm.password.update', 'Đổi mật khẩu', 'profile', 'Đổi mật khẩu của chính mình'),
('pm.team.view', 'Xem nhân sự', 'users', 'Xem danh sách nhân sự theo phạm vi'),
('pm.users.manage', 'Quản lý nhân sự', 'users', 'Tạo và cập nhật nhân sự'),
('pm.rbac.manage', 'Quản lý phân quyền', 'rbac', 'Quản lý role và permission'),
('pm.rates.view', 'Xem đơn giá', 'rates', 'Xem đơn giá theo phạm vi'),
('pm.rates.manage', 'Quản lý đơn giá', 'rates', 'Thiết lập đơn giá'),
('pm.projects.view', 'Xem dự án', 'projects', 'Xem dự án được phép'),
('pm.projects.manage', 'Quản lý dự án', 'projects', 'Tạo và cập nhật dự án'),
('pm.project_members.manage', 'Quản lý thành viên dự án', 'projects', 'Thêm hoặc cập nhật thành viên dự án'),
('pm.tasks.view', 'Xem công việc', 'tasks', 'Xem công việc được phép'),
('pm.tasks.manage', 'Quản lý công việc', 'tasks', 'Tạo và cập nhật công việc'),
('pm.timesheets.own', 'Quản lý chấm công cá nhân', 'timesheets', 'Ghi và xem chấm công cá nhân'),
('pm.timesheets.approve', 'Duyệt chấm công', 'timesheets', 'Duyệt chấm công theo phạm vi'),
('pm.reports.view', 'Xem báo cáo', 'reports', 'Xem báo cáo theo phạm vi'),
('pm.audit.view', 'Xem nhật ký kiểm toán', 'audit', 'Xem audit log theo phạm vi')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `module` = VALUES(`module`), `description` = VALUES(`description`);

INSERT INTO `dc_pm_roles` (`store_id`, `code`, `name`, `description`, `is_system`, `status`)
SELECT stores.`store_id`, role_seed.`code`, role_seed.`name`, role_seed.`description`, 1, 1
FROM (SELECT DISTINCT `store_id` FROM `dc_users`) stores
CROSS JOIN (
    SELECT 'ADMIN' AS `code`, 'Quản trị viên' AS `name`, 'Toàn quyền hệ thống PM' AS `description`
    UNION ALL SELECT 'PM', 'Quản lý dự án', 'Quản lý dự án, công việc và duyệt chấm công'
    UNION ALL SELECT 'HR', 'Nhân sự', 'Quản lý nhân sự, đơn giá và chấm công'
    UNION ALL SELECT 'EMPLOYEE', 'Nhân viên', 'Thao tác trong phạm vi cá nhân và dự án được giao'
) role_seed
WHERE 1 = 1
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`), `is_system` = 1, `status` = 1;

INSERT IGNORE INTO `dc_pm_role_permissions` (`role_id`, `permission_id`)
SELECT roles.`id`, permissions.`id` FROM `dc_pm_roles` roles CROSS JOIN `dc_pm_permissions` permissions
WHERE roles.`code` = 'ADMIN';

INSERT IGNORE INTO `dc_pm_role_permissions` (`role_id`, `permission_id`)
SELECT roles.`id`, permissions.`id` FROM `dc_pm_roles` roles
JOIN `dc_pm_permissions` permissions ON permissions.`code` IN (
    'pm.dashboard.view', 'pm.profile.view', 'pm.profile.update', 'pm.password.update',
    'pm.team.view', 'pm.projects.view', 'pm.projects.manage', 'pm.project_members.manage',
    'pm.tasks.view', 'pm.tasks.manage', 'pm.timesheets.own', 'pm.timesheets.approve', 'pm.reports.view')
WHERE roles.`code` = 'PM';

INSERT IGNORE INTO `dc_pm_role_permissions` (`role_id`, `permission_id`)
SELECT roles.`id`, permissions.`id` FROM `dc_pm_roles` roles
JOIN `dc_pm_permissions` permissions ON permissions.`code` IN (
    'pm.dashboard.view', 'pm.profile.view', 'pm.profile.update', 'pm.password.update',
    'pm.team.view', 'pm.users.manage', 'pm.rates.view', 'pm.rates.manage',
    'pm.timesheets.own', 'pm.timesheets.approve', 'pm.reports.view')
WHERE roles.`code` = 'HR';

INSERT IGNORE INTO `dc_pm_role_permissions` (`role_id`, `permission_id`)
SELECT roles.`id`, permissions.`id` FROM `dc_pm_roles` roles
JOIN `dc_pm_permissions` permissions ON permissions.`code` IN (
    'pm.dashboard.view', 'pm.profile.view', 'pm.profile.update', 'pm.password.update',
    'pm.projects.view', 'pm.tasks.view', 'pm.timesheets.own')
WHERE roles.`code` = 'EMPLOYEE';

-- Compatibility mapping: legacy admins/founders become ADMIN; other active users
-- begin as EMPLOYEE. Assignments can be refined without changing legacy type.
INSERT IGNORE INTO `dc_pm_user_roles` (`store_id`, `user_id`, `role_id`, `is_primary`)
SELECT users.`store_id`, users.`id`, roles.`id`, 1
FROM `dc_users` users
JOIN `dc_pm_roles` roles ON roles.`store_id` = users.`store_id`
 AND roles.`code` = CASE WHEN users.`type` IN (2, 3, 8, 9) THEN 'ADMIN' ELSE 'EMPLOYEE' END
WHERE users.`status` = 1;

-- Verification:
--   SELECT code FROM dc_pm_permissions ORDER BY code;
--   SELECT store_id, code FROM dc_pm_roles ORDER BY store_id, code;
--   SELECT COUNT(*) FROM dc_pm_user_roles;
