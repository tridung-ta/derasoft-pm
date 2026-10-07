<?php
/** Input checks only: these do not verify mailbox or telephone ownership. */
class PmUserInputException extends InvalidArgumentException {
    public function __construct(private array $fieldErrors){parent::__construct(implode(' ',$fieldErrors));}
    public function getFieldErrors(): array{return $this->fieldErrors;}
}
class PmUserInputService {
    public static function validateCreate(string $email,string $password,string $phone): void {
        $errors=[];
        if(strlen($email)>50||!filter_var($email,FILTER_VALIDATE_EMAIL)){
            $errors['email']='Email không đúng định dạng hoặc vượt quá 50 ký tự.';
        }
        if($password==='')$errors['password']='Vui lòng nhập mật khẩu.';
        elseif(trim($password)==='')$errors['password']='Mật khẩu không được chỉ gồm khoảng trắng.';
        elseif(mb_strlen(trim($password),'UTF-8')<8)$errors['password']='Mật khẩu phải có ít nhất 8 ký tự.';
        if($phone!==''&&!preg_match('/^[0-9]+$/D',$phone))$errors['tel']='Điện thoại chỉ được chứa chữ số từ 0 đến 9, không có chữ, khoảng trắng hoặc ký tự đặc biệt.';
        elseif(strlen($phone)>10)$errors['tel']='Điện thoại không được vượt quá 10 số.';
        if($errors)throw new PmUserInputException($errors);
    }
}
