--TEST--
--FILE--
<?php
$a > $b;
?>
--PASSES--
lower-binary-ops,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Lower_Binary_Op_5.php">
    <block>
        <binaryOp op="LESS_THAN">
            <var line="2" id="b" />
            <var line="2" id="a" />
        </binaryOp>
    </block>
</PHP_source>


