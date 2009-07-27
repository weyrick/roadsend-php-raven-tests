--TEST--
basic user functions
--FILE--
<?php

function func0() {
    echo "func0\n";
}

function func1($a) {
    echo "func1: ";
    echo $a;
    echo "\n";
}

function func2($a, $b) {
    echo "func2: ";
    echo $a;
    echo $b;
    echo "\n";
}

function func3($a, $b, $c) {
    echo "func3: ";
    echo $a;
    echo $b;
    echo $c;
    echo "\n";
}

function func4($a, $b, $c, $d) {
    echo "func4: ";
    echo $a;
    echo $b;
    echo $c;
    echo $d;
    echo "\n";
}

function func5($a, $b, $c, $d, $e) {
    echo "func5: ";
    echo $a;
    echo $b;
    echo $c;
    echo $d;
    echo $e;
    echo "\n";
}

func0();
func1(1);
func2(1,2);
func3(1,2,3);
func4(1,2,3,4);
func5(1,2,3,4,5);

?>
--EXPECT--
func0
func1: 1
func2: 12
func3: 123
func4: 1234
func5: 12345
