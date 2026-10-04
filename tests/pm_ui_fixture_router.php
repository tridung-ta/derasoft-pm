<?php
// Local preview server only; no authenticated session/data or production route.
require dirname(__DIR__).'/includes/pm_response.inc.php';
pmResponseHeaders();
return false;
