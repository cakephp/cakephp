<?php
if (class_exists('PHPUnit_Runner_Version')) {
    if (version_compare(\PHPUnit_Runner_Version::id(), '5.7', '<')) {
        trigger_error(sprintf('Your PHPUnit Version must be at least 5.7.0 to use CakePHP Testsuite, found %s', \PHPUnit_Runner_Version::id()), E_USER_ERROR);
    }

    if (!class_exists('PHPUnit\Runner\Version')) {
        class_alias('PHPUnit_Framework_Constraint', 'PHPUnit\Framework\Constraint\Constraint');
        class_alias('PHPUnit_Framework_Test', 'PHPUnit\Framework\Test');
        class_alias('PHPUnit_Framework_AssertionFailedError', 'PHPUnit\Framework\AssertionFailedError');
        class_alias('PHPUnit_Framework_TestSuite', 'PHPUnit\Framework\TestSuite');
        class_alias('PHPUnit_Framework_TestResult', 'PHPUnit\Framework\TestResult');
        class_alias('PHPUnit_Framework_Error', 'PHPUnit\Framework\Error\Error');
        class_alias('PHPUnit_Framework_Error_Deprecated', 'PHPUnit\Framework\Error\Deprecated');
        class_alias('PHPUnit_Framework_Error_Notice', 'PHPUnit\Framework\Error\Notice');
        class_alias('PHPUnit_Framework_Error_Warning', 'PHPUnit\Framework\Error\Warning');
        class_alias('PHPUnit_Framework_ExpectationFailedException', 'PHPUnit\Framework\ExpectationFailedException');
    }
}

if (!class_exists('PHPUnit\Framework\MockObject\MockBuilder')) {
    class_alias('PHPUnit_Framework_MockObject_MockBuilder', 'PHPUnit\Framework\MockObject\MockBuilder');
}
