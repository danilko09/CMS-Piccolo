<?php

function assertEquals($actual, $expected, $message = null){
    if($actual !== $expected){
        $usrmsg = $message != null ? $message."\n" : "";
        $assmsg = "Expected: ".var_export($expected, true).", but was: ".var_export($actual, true);
        throw new AssertionError($usrmsg.$assmsg);
    }
}

function assertTrue($assertion, $message = null){
    assertEquals($assertion, true, $message);    
}

function assertFalse($assertion, $message = null){
    assertEquals($assertion, false, $message);    
}