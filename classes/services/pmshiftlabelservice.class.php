<?php
/** Shift names are presentation labels, never inputs to hours/OT calculation. */
class PmShiftLabelService {
    public static function choices(): array {
        return ['Ca sáng'=>'Ca sáng','Ca chiều'=>'Ca chiều','Ca tối'=>'Ca tối','Ca linh hoạt'=>'Ca linh hoạt'];
    }
    public static function validate($value,?string $original=null): string {
        if(!is_string($value))throw new InvalidArgumentException('Vui lòng chọn nhãn ca trong danh sách.');
        // Preserve an unchanged existing label; never classify historical free text.
        if($original!==null&&$value===$original&&trim($original)!==''&&mb_strlen($original)<=30)return $original;
        if(preg_match('/[\x00-\x1F\x7F]/',$value))throw new InvalidArgumentException('Vui lòng chọn nhãn ca trong danh sách.');
        $value=trim($value);
        if(mb_strlen($value)>30||!array_key_exists($value,self::choices()))throw new InvalidArgumentException('Vui lòng chọn nhãn ca trong danh sách.');
        return $value;
    }
}