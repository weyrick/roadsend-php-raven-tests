--TEST--
basic array functionality
--FILE--
<?php

$ary = array(1,2,3);
var_dump($ary);

$ary = array(5 => "five");
var_dump($ary);

$ary = array("five" => 5);
var_dump($ary);

?>
--EXPECT--
array(3) {
  [0]=>
  int(1)
  [1]=>
  int(2)
  [2]=>
  int(3)
}
array(1) {
  [5]=>
  unicode(4) "five"
}
array(1) {
  [u"five"]=>
  int(5)
}
