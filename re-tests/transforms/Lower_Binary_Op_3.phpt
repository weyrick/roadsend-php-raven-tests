--TEST--
--FILE--
<?php
$a xor $b;
?>
--PASSES--
lower-binary-ops,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Lower_Binary_Op_3.php">
    <block>
        <conditionalExpr>
            <conditionalExpr>
                <conditionalExpr>
                    <var line="2" id="a" />
                    <typeCast castKind="bool">
                        <unaryOp op="LOGICALNOT">
                            <var line="2" id="b" />
                        </unaryOp>
                    </typeCast>
                    <literalBool value="false" />
                </conditionalExpr>
                <literalBool value="true" />
                <typeCast castKind="bool">
                    <conditionalExpr>
                        <var line="2" id="b" />
                        <typeCast castKind="bool">
                            <unaryOp op="LOGICALNOT">
                                <var line="2" id="a" />
                            </unaryOp>
                        </typeCast>
                        <literalBool value="false" />
                    </conditionalExpr>
                </typeCast>
            </conditionalExpr>
            <literalBool value="true" />
            <literalBool value="false" />
        </conditionalExpr>
    </block>
</PHP_source>


