--TEST--
--FILE--
<?php
$a && $b;
?>
--PASSES--
lower-binary-ops,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Lower_Binary_Op_1.php">
    <block>
        <conditionalExpr>
            <var line="2" id="a" />
            <typeCast castKind="bool">
                <var line="2" id="b" />
            </typeCast>
            <literalBool value="false" />
        </conditionalExpr>
    </block>
</PHP_source>


