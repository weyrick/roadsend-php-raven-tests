--TEST--
--FILE--
<?php
$a ? true : false;
?>
--PASSES--
lower-conditional-exprs,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Lower_Conditional_Exprs.php">
    <block>
        <exprReduce>
            <block>
                <ifStmt>
                    <var line="2" id="a" />
                    <block>
                        <assignment byRef="false">
                            <var id=".ret0" />
                            <literalBool line="2" value="true" />
                        </assignment>
                    </block>
                    <block>
                        <assignment byRef="false">
                            <var id=".ret0" />
                            <literalBool line="2" value="false" />
                        </assignment>
                    </block>
                </ifStmt>
                <var id=".ret0" />
            </block>
        </exprReduce>
    </block>
</PHP_source>



