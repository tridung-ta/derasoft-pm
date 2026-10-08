<?php
/** Test-only assertions on real writer output, not a source-code grep. */
function assertPmAuditIdentity(PmDb $q,int $store,array $actors,string $entity,array $actions): void {
    global $auditIdentityBaseline;
    if(!isset($auditIdentityBaseline))throw new RuntimeException('Missing audit baseline.');
    foreach($actions as $action){
        $rows=$q->fetchAll('SELECT * FROM dc_pm_audit_logs WHERE store_id=? AND entity_type=? AND action=? AND id>?','issi',[$store,$entity,$action,$auditIdentityBaseline]);
        if(!$rows)throw new RuntimeException('Audit identity coverage absent: '.$entity.'/'.$action);
        foreach($rows as $row){
            if(!in_array((int)$row['actor_id'],$actors,true))throw new RuntimeException('Wrong audit actor: '.$entity.'/'.$action);
            foreach(['old_values','new_values'] as $column){
                if($row[$column]===null)continue;
                $values=json_decode($row[$column],true,512,JSON_THROW_ON_ERROR);
                foreach(['actor_name','actor_username','username','fullname','email','tel','telephone','password','password_hash','ip','remote_addr'] as $key)
                    if(array_key_exists($key,$values))throw new RuntimeException('Identity/contact/credential key leaked: '.$entity.'/'.$action.'/'.$key);
            }
        }
        echo 'PASS ID-only writer: '.$entity.'/'.$action."\n";
    }
}
