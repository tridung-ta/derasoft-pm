<?php
include_once(ROOT_PATH.'classes/database/pmdb.class.php');
class PmSessionGuard {
    public static function valid($database,int $storeId,int $actorId,?int $sessionStore): bool {
        if($actorId<=0||($sessionStore!==null&&$sessionStore!==$storeId))return false;
        return (new PmDb($database))->fetchOne('SELECT id FROM dc_users WHERE store_id=? AND id=? AND status=1','ii',[$storeId,$actorId])!==null;
    }
}
