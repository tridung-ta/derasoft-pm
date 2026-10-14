<?php
// Opt-in only after migration 011 and host UAT. No secret or host config in Git.
if (!defined('PM_REMINDERS_ENABLED')) define('PM_REMINDERS_ENABLED', getenv('PM_REMINDERS_ENABLED') === '1');
