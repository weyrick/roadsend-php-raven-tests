--TEST--
Turns return; into return NULL;
--FILE--
<?php

return NULL;
return;

?>
--PASSES--
desugar,dump-ast
--EXPECTPARSE--
<?xml version="1.0" ?>
<PHP_source file="./re-output/Desugar.php">
    <block>
        <returnStmt line="3">
            <literalNull line="3" />
        </returnStmt>
        <returnStmt line="4">
            <literalNull />
        </returnStmt>
    </block>
</PHP_source>

