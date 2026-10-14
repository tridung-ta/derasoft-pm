# MariaDB notification metadata compatibility - 2026-10-14

Operator evidence: VERSION=10.6.28-MariaDB, DATABASE=information_schema; dung_pm due_date metadata is DATE/YES, COLUMN_DEFAULT unquoted NULL, SQL IS NULL false, HEX=4E554C4C. Wrong selected database and metadata representation prevent migration012 guard from passing. Source: https://mariadb.com/docs/server/reference/system-tables/information-schema/information-schema-tables/information-schema-columns-table

Local fix: migration012 and read-only preflight accept SQL NULL or exact unquoted NULL metadata for nullable DATE. PHP preflight accepts those same two representations for read_at/due_date. Quoted string defaults, date defaults, empty defaults and NOT NULL remain rejected. No DDL meaning, key definition or stored data changed.

Verification: powershell -NoProfile -ExecutionPolicy Bypass -File tests/pm_workspace_verify.ps1 exit0:19 no-DB scripts,23 PHP lint,6 manifest tests. New recording DAO tests cover MariaDB NULL acceptance and quoted NULL/date/empty rejection. Log .local/mariadb-null-verify.log. SQL execution and native MariaDB integration NOT RUN; operator must run corrected012 in dung_pm after backup and send output. Do not enable flag/cron yet.
