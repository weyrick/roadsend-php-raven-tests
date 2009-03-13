--TEST--
basic literal assignment and var_dump
--FILE--
<?php

$null = null;
var_dump($null);

$btrue = true;
var_dump($btrue);

$bfalse = false;
var_dump($bfalse);

$num = 5;
var_dump($num);

$float = 1.234;
var_dump($float);

?>
--EXPECT--
NULL
bool(true)
bool(false)
int(5)
float(1.234)
