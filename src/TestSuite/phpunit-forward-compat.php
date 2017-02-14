<?php

if (class_exists('PHPUnit_Runner_Version')) {
    class_alias('PHPUnit_Framework_Test', 'PHPUnit\Framework\Test');
    class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\Framework\TestSuite');
    class_alias('PHPUnit_Exception', 'PHPUnit\Exception');
    class_alias('PHPUnit_Framework_Error_Warning', 'PHPUnit\Framework\Error\Warning');
    class_alias('PHPUnit_Framework_Constraint', 'PHPUnit\Framework\Constraint\Constraint');
    class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
}
