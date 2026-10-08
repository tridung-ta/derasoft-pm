<?php
/** Validate entity references before integer casts; zero/blank only for optional IDs. */
class PmIdInput {
    public static function parse($value,bool $optional=false): int {
        if($optional&&($value===null||$value===''||$value==='0'||$value===0))return 0;
        if(!is_scalar($value)||!preg_match('/^[1-9]\d{0,17}$/D',(string)$value)||filter_var($value,FILTER_VALIDATE_INT)===false)throw new InvalidArgumentException('ID không hợp lệ.');
        return (int)$value;
    }
}
