--TEST--
SimplifyStrings pass will convert double quoted strings to single quoted versions, using string concatenation operator as necessary
--FILE--
<?php

echo "$bar";
echo "foo $bar";
echo "foo $bar baz";
echo "$bar baz";
echo "$bar baz $foo";

?>
--PASSES--
simplify-strings,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/SimplifyStrings.php">
    <block>
        <builtin line="3" op="ECHO">
            <var id="bar" />
        </builtin>
        <builtin line="4" op="ECHO">
            <binaryOp op="CONCAT">
                <literalString type="binary" simple="yes">foo </literalString>
                <var id="bar" />
            </binaryOp>
        </builtin>
        <builtin line="5" op="ECHO">
            <binaryOp op="CONCAT">
                <literalString type="binary" simple="yes">foo </literalString>
                <binaryOp op="CONCAT">
                    <var id="bar" />
                    <literalString type="binary" simple="yes"> baz</literalString>
                </binaryOp>
            </binaryOp>
        </builtin>
        <builtin line="6" op="ECHO">
            <binaryOp op="CONCAT">
                <var id="bar" />
                <literalString type="binary" simple="yes"> baz</literalString>
            </binaryOp>
        </builtin>
        <builtin line="7" op="ECHO">
            <binaryOp op="CONCAT">
                <var id="bar" />
                <binaryOp op="CONCAT">
                    <literalString type="binary" simple="yes"> baz </literalString>
                    <var id="foo" />
                </binaryOp>
            </binaryOp>
        </builtin>
    </block>
</PHP_source>