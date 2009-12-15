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

$bstr = b"hello";
var_dump($bstr);

$ustr = "hello";
var_dump($ustr);

$ary = array(1,2,3);
var_dump($ary);

?>
--EXPECTPARSE--
foo
--EXPECT--
NULL
bool(true)
bool(false)
int(5)
float(1.234)
string(5) "hello"
unicode(5) "hello"
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
