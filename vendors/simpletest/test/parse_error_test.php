<?php
    // $Id$
    
    require_once('../unit_tester.php');
    require_once('../reporter.php');

    $test = &new GroupTest('This should fail');
    $test->addTestFile('test_with_parse_error.php');
    $test->run(new HtmlReporter());
?>