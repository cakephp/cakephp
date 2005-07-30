<?php
// $Id: collector_test.php,v 1.7 2005/07/27 17:19:20 lastcraft Exp $

require_once(dirname(__FILE__) . '/../collector.php');
Mock::generate('GroupTest');

class PathEqualExpectation extends EqualExpectation {
	function PathEqualExpectation($value, $message = '%s') {
    	$this->EqualExpectation(str_replace('\\', '/', $value), $message);
	}
	
    function test($compare) {
        return parent::test(str_replace('\\', '/', $compare));
    }
}

class TestOfCollector extends UnitTestCase {
    
    function testCollectionIsAddedToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectMinimumCallCount('addTestFile', 2);
        $group->expectArguments(
                'addTestFile',
                array(new PatternExpectation('/collectable\\.(1|2)$/')));
        
        $collector = &new SimpleCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/collector/');
        
        $group->tally();
    }
}
    
class TestOfPatternCollector extends UnitTestCase {
    
    function testAddingEverythingToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectCallCount('addTestFile', 2);
        $group->expectArguments(
                'addTestFile',
                array(new PatternExpectation('/collectable\\.(1|2)$/')));
        
        $collector = &new SimplePatternCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/collector/', '/.*/');
        
        $group->tally();
    }
        
    function testOnlyMatchedFilesAreAddedToGroup() {
        $group = &new MockGroupTest($this);
        $group->expectOnce('addTestFile', array(new PathEqualExpectation(
        		dirname(__FILE__) . '/support/collector/collectable.1')));
        
        $collector = &new SimplePatternCollector();
        $collector->collect($group, dirname(__FILE__) . '/support/collector/', '/1$/');
        
        $group->tally();
    }
}
?>