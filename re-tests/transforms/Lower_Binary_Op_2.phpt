--TEST--
--FILE--
<?php
$a || $b;
?>
--PASSES--
lower-binary-ops,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Lower_Binary_Op_2.php">
    <block>
        <conditionalExpr>
            <var line="2" id="a" />
            <literalBool value="true" />
            <typeCast castKind="bool">
                <var line="2" id="b" />
            </typeCast>
        </conditionalExpr>
    </block>
</PHP_source>



