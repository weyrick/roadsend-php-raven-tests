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

?>
--EXPECT--
int(5)
int(5)
int(-5)
int(9999999999999999999999999999999)
int(-9999999999999999999999999999999)
