--TEST--
basic interger assignment and operations
--FILE--
<?php

$num = +5;
var_dump($num);

$num = 5;
var_dump($num);

$num = -5;
var_dump($num);

$num = 9999999999999999999999999999999;
var_dump($num);

$num = -9999999999999999999999999999999;
var_dump($num);

$num = 0xf;
var_dump($num);

$num = 0xffffffffffffffffffffffffffffff;
var_dump($num);

$num = -0xffffffffff;
var_dump($num);

$num = 0777;
var_dump($num);

$num = -0765;
var_dump($num);

?>
--EXPECT--
int(5)
int(5)
int(-5)
int(9999999999999999999999999999999)
int(-9999999999999999999999999999999)
int(15)
int(1329227995784915872903807060280344575)
int(-1099511627775)
int(511)
int(-501)
