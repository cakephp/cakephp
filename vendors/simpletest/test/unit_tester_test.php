<?php
    // $Id$
    
    class ReferenceForTesting {
    }
    
    class TestOfUnitTester extends UnitTestCase {
        
        function testAssertTrueReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertTrue(true));
        }
        
        function testAssertFalseReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertFalse(false));
        }
        
        function testAssertEqualReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertEqual(5, 5));
        }
        
        function testAssertIdenticalReturnsAssertionAsBoolean() {
            $this->assertTrue($this->assertIdentical(5, 5));
        }
        
        function testCoreAssertionsDoNotThrowErrors() {
            $this->assertIsA($this, 'UnitTestCase');
            $this->assertNotA($this, 'WebTestCase');
        }
        
        function testReferenceAssertionOnObjects() {
            $a = &new ReferenceForTesting();
            $b = &$a;
            $this->assertReference($a, $b);
        }
        
        function testReferenceAssertionOnScalars() {
            $a = 25;
            $b = &$a;
            $this->assertReference($a, $b);
        }
        
        function testCloneOnObjects() {
            $a = &new ReferenceForTesting();
            $b = &new ReferenceForTesting();
            $this->assertCopy($a, $b);
        }
        
        function testCloneOnScalars() {
            $a = 25;
            $b = 25;
            $this->assertCopy($a, $b);
        }
    }
    
    class JBehaveStyleRunner extends SimpleRunner {
        function JBehaveStyleRunner(&$test_case, &$scorer) {
            $this->SimpleRunner($test_case, $scorer);
        }
        
        function _isTest($method) {
            return strtolower(substr($method, 0, 6)) == 'should';
        }
    }
    
    class TestOfJBehaveStyleRunner extends UnitTestCase {
        
        function &_createRunner(&$reporter) {
            $runner = &new JBehaveStyleRunner($this, $reporter);
            return $runner;
        }
        
        function testFail() {
            $this->fail('This should not be run');
        }
        
        function shouldBeRun() {
            $this->pass('This should be run');
        }
    }
?>