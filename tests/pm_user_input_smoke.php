<?php
require dirname(__DIR__).'/classes/services/pmuserinputservice.class.php';
$cases=0;
foreach(['','0','0901234567'] as $phone){
    PmUserInputService::validateCreate('person@example.test','password123',$phone);$cases++;
}
foreach([
    ['person@example.test','',''],
    ['person@example.test','1234567',''],
    ['person@example.test','        ',''],
    ['person@example.test','áááá',''],
    ['invalid','password123',''],
    [str_repeat('a',40).'@example.test','password123',''],
    ['person@example.test','password123','090abc1234'],
    ['person@example.test','password123','09012345678'],
    ['person@example.test','password123','+84901234567'],
    ['person@example.test','password123','090 123456'],
    ['person@example.test','password123',"0901234567\n"],
] as $input){
    try{PmUserInputService::validateCreate(...$input);throw new RuntimeException('Invalid personnel input accepted.');}
    catch(InvalidArgumentException $e){$cases++;}
}
foreach([
    ['', 'password', 'Vui lòng nhập mật khẩu.'],
    ['1234567', 'password', 'Mật khẩu phải có ít nhất 8 ký tự.'],
    ['        ', 'password', 'Mật khẩu không được chỉ gồm khoảng trắng.'],
] as [$password,$field,$message]){
    try{PmUserInputService::validateCreate('person@example.test',$password,'');throw new RuntimeException('Invalid password accepted.');}
    catch(PmUserInputException $e){if(($e->getFieldErrors()[$field]??'')!==$message)throw new RuntimeException('Wrong password message.');$cases++;}
}
foreach(['090abc1234'=>'Điện thoại chỉ được chứa chữ số từ 0 đến 9, không có chữ, khoảng trắng hoặc ký tự đặc biệt.','09012345678'=>'Điện thoại không được vượt quá 10 số.'] as $phone=>$message){
    try{PmUserInputService::validateCreate('person@example.test','password123',$phone);throw new RuntimeException('Invalid phone accepted.');}
    catch(PmUserInputException $e){if(($e->getFieldErrors()['tel']??'')!==$message)throw new RuntimeException('Wrong phone message.');$cases++;}
}
PmUserInputService::validateCreate('person@example.test','áááááááá','');$cases++;
try{PmUserInputService::validateCreate('person@example.test','','abc');throw new RuntimeException('Multiple errors accepted.');}
catch(PmUserInputException $e){if(!isset($e->getFieldErrors()['password'],$e->getFieldErrors()['tel']))throw new RuntimeException('Missing simultaneous errors.');$cases++;}
echo "PASS: $cases create input boundaries; no DB/network writes, no mailbox/phone existence claim.\n";
