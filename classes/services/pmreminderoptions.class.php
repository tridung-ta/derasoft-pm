<?php
/** Parse scope before opening a database connection. */
class PmReminderOptions {
    public const USER_ERROR='Danh sách người nhận không hợp lệ.';
    public static function parse(array $args): array {
        $o=['commit'=>false,'store'=>null,'job'=>'all','date'=>null,'user_ids'=>[],'all_users'=>false];$seen=[];
        foreach($args as $arg){
            $key=explode('=',$arg,2)[0];if(isset($seen[$key]))throw new InvalidArgumentException('Tham số bị lặp.');$seen[$key]=true;
            if($arg==='--commit')$o['commit']=true;
            elseif($arg==='--all-users')$o['all_users']=true;
            elseif(preg_match('/^--store-id=([1-9][0-9]{0,17})$/D',$arg,$m)&&filter_var($m[1],FILTER_VALIDATE_INT)!==false)$o['store']=(int)$m[1];
            elseif(preg_match('/^--job=(all|task|timesheet)$/D',$arg,$m))$o['job']=$m[1];
            elseif(str_starts_with($arg,'--user-ids=')){
                $ids=explode(',',substr($arg,11));if(count($ids)>100)throw new InvalidArgumentException(self::USER_ERROR);
                foreach($ids as $id){if(!preg_match('/^[1-9][0-9]{0,17}$/D',$id)||filter_var($id,FILTER_VALIDATE_INT)===false)throw new InvalidArgumentException(self::USER_ERROR);$o['user_ids'][]=(int)$id;}
                if(count(array_unique($o['user_ids']))!==count($o['user_ids']))throw new InvalidArgumentException(self::USER_ERROR);
            }elseif(preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/D',$arg,$m))$o['date']=$m[1];
            else throw new InvalidArgumentException('Tham số không hợp lệ.');
        }
        if(!$o['store']||($o['commit']&&$o['date']!==null))throw new InvalidArgumentException('Cần đơn vị hợp lệ; không đổi ngày khi ghi thật.');
        if(($o['user_ids']&&$o['all_users'])||($o['commit']&&!$o['user_ids']&&!$o['all_users']))throw new InvalidArgumentException('Chọn riêng --user-ids hoặc --all-users khi ghi thật.');
        return $o;
    }
}
