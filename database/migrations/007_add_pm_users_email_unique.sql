-- Approved by user for backed-up LOCAL execution; no production authorization.
-- Adds DB-level email uniqueness inside each tenant; does NOT change ENGINE/data.
-- Run tests/pm_import_email_index_audit.php first, backup local/staging, arrange
-- maintenance: MyISAM ALTER may rebuild/lock dc_users and block ALL user writes.
-- Existing non-unique email index remains. NULL emails remain nullable.
-- Existing duplicate non-NULL (store_id,email) makes native ADD UNIQUE fail;
-- do not clean/change data automatically to make this migration pass.
-- Idempotent for the exact expected index; a conflicting name/definition fails.
SET @pm_email_unique_exists = (
 SELECT COUNT(*) FROM (
  SELECT index_name FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='dc_users'
   AND index_name='uq_pm_users_store_email'
  GROUP BY index_name
  HAVING COUNT(*)=2 AND MIN(non_unique)=0 AND MAX(non_unique)=0
   AND SUM(sub_part IS NOT NULL)=0
   AND GROUP_CONCAT(column_name ORDER BY seq_in_index SEPARATOR ',')='store_id,email'
 ) pm_index
);
SET @pm_email_unique_sql = IF(@pm_email_unique_exists=1,
 'SELECT ''uq_pm_users_store_email already exists'' AS migration_status',
 'ALTER TABLE dc_users ADD UNIQUE INDEX uq_pm_users_store_email (store_id,email)');
PREPARE pm_email_unique_statement FROM @pm_email_unique_sql;
EXECUTE pm_email_unique_statement;
DEALLOCATE PREPARE pm_email_unique_statement;
-- Rollback application: disable Apply, retain index. Do not DROP INDEX under
-- current policy; removing a core-table constraint requires separate approval.
