<?php
/** Input checks only: these do not verify mailbox or telephone ownership. */
class PmUserInputService {
    public static function validateCreate(string $email,string $password,string $phone): void {
        if(strlen($email)>50||!filter_var($email,FILTER_VALIDATE_EMAIL)){
            throw new InvalidArgumentException('Email không đúng định dạng hoặc vượt quá 50 ký tự.');
        }
        if(strlen($password)<8||trim($password)===''){
            throw new InvalidArgumentException('Mật khẩu bắt buộc có ít nhất 8 ký tự và không được chỉ gồm khoảng trắng.');
        }
        if($phone!==''&&!preg_match('/^[0-9]{1,10}$/D',$phone)){
            throw new InvalidArgumentException('Điện thoại chỉ được chứa chữ số từ 0 đến 9, tối đa 10 số; có thể để trống.');
        }
    }
}
