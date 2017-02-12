<?php

if (!class_exists('PHPUnit\Runner\Version')) {
    class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\Framework\TestSuite');
    class_alias('PHPUnit_Framework_Test', 'PHPUnit\Framework\Test');
    class_alias('PHPUnit_Framework_TestResult', 'PHPUnit\Framework\TestResult');
    class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
    class_alias('PHPUnit_Framework_Error', 'PHPUnit\Framework\Error\Error');
    class_alias('PHPUnit_Framework_Error_Warning', 'PHPUnit\Framework\Error\Warning');
    class_alias('PHPUnit_Framework_Constraint', 'PHPUnit\Framework\Constraint\Constraint');
    class_alias('PHPUnit_Framework_ExpectationFailedException', 'PHPUnit\Framework\ExpectationFailedException');
}
