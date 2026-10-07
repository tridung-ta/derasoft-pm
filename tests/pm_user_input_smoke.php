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
echo "PASS: $cases create input boundaries; no DB/network writes, no mailbox/phone existence claim.\n";
