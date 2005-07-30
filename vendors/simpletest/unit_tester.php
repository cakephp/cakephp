<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	UnitTester
     *	@version	$Id$
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/simple_test.php');
    require_once(dirname(__FILE__) . '/errors.php');
    require_once(dirname(__FILE__) . '/dumper.php');
    /**#@-*/
    
    /**
     *    Standard unit test class for day to day testing
     *    of PHP code XP style. Adds some useful standard
     *    assertions.
	 *	  @package	SimpleTest
	 *	  @subpackage	UnitTester
     */
    class UnitTestCase extends SimpleTestCase {
        
        /**
         *    Creates an empty test case. Should be subclassed
         *    with test methods for a functional test case.
         *    @param string $label     Name of test case. Will use
         *                             the class name if none specified.
         *    @access public
         */
        function UnitTestCase($label = false) {
            if (! $label) {
                $label = get_class($this);
            }
            $this->SimpleTestCase($label);
        }
        
        /**
         *    Will be true if the value is null.
         *    @param null $value       Supposedly null value.
         *    @param string $message   Message to display.
         *    @return boolean                        True on pass
         *    @access public
         */
        function assertNull($value, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($value) . "] should be null");
            return $this->assertTrue(! isset($value), $message);
        }
        
        /**
         *    Will be true if the value is set.
         *    @param mixed $value           Supposedly set value.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass.
         *    @access public
         */
        function assertNotNull($value, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($value) . "] should not be null");
            return $this->assertTrue(isset($value), $message);
        }
        
        /**
         *    Type and class test. Will pass if class
         *    matches the type name or is a subclass or
         *    if not an object, but the type is correct.
         *    @param mixed $object         Object to test.
         *    @param string $type          Type name as string.
         *    @param string $message       Message to display.
         *    @return boolean              True on pass.
         *    @access public
         */
        function assertIsA($object, $type, $message = "%s") {
            return $this->assert(
                    new IsAExpectation($type),
                    $object,
                    $message);
        }
        
        /**
         *    Type and class mismatch test. Will pass if class
         *    name or underling type does not match the one
         *    specified.
         *    @param mixed $object         Object to test.
         *    @param string $type          Type name as string.
         *    @param string $message       Message to display.
         *    @return boolean              True on pass.
         *    @access public
         */
        function assertNotA($object, $type, $message = "%s") {
            return $this->assert(
                    new NotAExpectation($type),
                    $object,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value only. Otherwise a fail.
         *    @param mixed $first          Value to compare.
         *    @param mixed $second         Value to compare.
         *    @param string $message       Message to display.
         *    @return boolean              True on pass
         *    @access public
         */
        function assertEqual($first, $second, $message = "%s") {
            return $this->assert(
                    new EqualExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    a different value. Otherwise a fail.
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass
         *    @access public
         */
        function assertNotEqual($first, $second, $message = "%s") {
            return $this->assert(
                    new NotEqualExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the if the first parameter
         *    is near enough to the second by the margin.
         *    @param mixed $first          Value to compare.
         *    @param mixed $second         Value to compare.
         *    @param mixed $margin         Fuzziness of match.
         *    @param string $message       Message to display.
         *    @return boolean              True on pass
         *    @access public
         */
        function assertWithinMargin($first, $second, $margin, $message = "%s") {
            return $this->assert(
                    new WithinMarginExpectation($first, $margin),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters differ
         *    by more than the margin.
         *    @param mixed $first          Value to compare.
         *    @param mixed $second         Value to compare.
         *    @param mixed $margin         Fuzziness of match.
         *    @param string $message       Message to display.
         *    @return boolean              True on pass
         *    @access public
         */
        function assertOutsideMargin($first, $second, $margin, $message = "%s") {
            return $this->assert(
                    new OutsideMarginExpectation($first, $margin),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the same value and same type. Otherwise a fail.
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass
         *    @access public
         */
        function assertIdentical($first, $second, $message = "%s") {
            return $this->assert(
                    new IdenticalExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if the two parameters have
         *    the different value or different type.
         *    @param mixed $first           Value to compare.
         *    @param mixed $second          Value to compare.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass
         *    @access public
         */
        function assertNotIdentical($first, $second, $message = "%s") {
            return $this->assert(
                    new NotIdenticalExpectation($first),
                    $second,
                    $message);
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to the same object. Fail otherwise.
         *    @param mixed $first           Object reference to check.
         *    @param mixed $second          Hopefully the same object.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass
         *    @access public
         */
        function assertReference(&$first, &$second, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($first) .
                            "] and [" . $dumper->describeValue($second) .
                            "] should reference the same object");
            return $this->assertTrue(
                    SimpleTestCompatibility::isReference($first, $second),
                    $message);
        }
        
        /**
         *    Will trigger a pass if both parameters refer
         *    to different objects. Fail otherwise.
         *    @param mixed $first           Object reference to check.
         *    @param mixed $second          Hopefully not the same object.
         *    @param string $message        Message to display.
         *    @return boolean               True on pass
         *    @access public
         */
        function assertCopy(&$first, &$second, $message = "%s") {
            $dumper = &new SimpleDumper();
            $message = sprintf(
                    $message,
                    "[" . $dumper->describeValue($first) .
                            "] and [" . $dumper->describeValue($second) .
                            "] should not be the same object");
            return $this->assertFalse(
                    SimpleTestCompatibility::isReference($first, $second),
                    $message);
        }
        
        /**
         *    Will trigger a pass if the Perl regex pattern
         *    is found in the subject. Fail otherwise.
         *    @param string $pattern    Perl regex to look for including
         *                              the regex delimiters.
         *    @param string $subject    String to search in.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertPattern($pattern, $subject, $message = "%s") {
            return $this->assert(
                    new PatternExpectation($pattern),
                    $subject,
                    $message);
        }
        
        /**
         *	  @deprecated
         */
        function assertWantedPattern($pattern, $subject, $message = "%s") {
        	return $this->assertPattern($pattern, $subject, $message);
        }
        
        /**
         *    Will trigger a pass if the perl regex pattern
         *    is not present in subject. Fail if found.
         *    @param string $pattern    Perl regex to look for including
         *                              the regex delimiters.
         *    @param string $subject    String to search in.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertNoPattern($pattern, $subject, $message = "%s") {
            return $this->assert(
                    new NoPatternExpectation($pattern),
                    $subject,
                    $message);
        }
        
        /**
         *	  @deprecated
         */
        function assertNoUnwantedPattern($pattern, $subject, $message = "%s") {
        	return $this->assertNoPattern($pattern, $subject, $message);
        }
        
        /**
         *    Confirms that no errors have occoured so
         *    far in the test method.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertNoErrors($message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            return $this->assertTrue(
                    $queue->isEmpty(),
                    sprintf($message, "Should be no errors"));
        }
        
        /**
         *    Confirms that an error has occoured and
         *    optionally that the error text matches exactly.
         *    @param string $expected   Expected error text or
         *                              false for no check.
         *    @param string $message    Message to display.
         *    @return boolean           True on pass
         *    @access public
         */
        function assertError($expected = false, $message = "%s") {
            $queue = &SimpleErrorQueue::instance();
            if ($queue->isEmpty()) {
                $this->fail(sprintf($message, "Expected error not found"));
                return;
            }
            list($severity, $content, $file, $line, $globals) = $queue->extract();
            $severity = SimpleErrorQueue::getSeverityAsString($severity);
            if (! $expected) {
                return $this->pass(
                        "Captured a PHP error of [$content] severity [$severity] in [$file] line [$line] -> %s");
            }
            $expected = $this->_coerceToExpectation($expected);
            return $this->assert(
                    $expected,
                    $content,
                    "Expected PHP error [$content] severity [$severity] in [$file] line [$line] -> %s");
        }
        
        /**
         *    Creates an equality expectation if the
         *    object/value is not already some type
         *    of expectation.
         *    @param mixed $expected      Expected value.
         *    @return SimpleExpectation   Expectation object.
         *    @access private
         */
        function _coerceToExpectation($expected) {
            if (SimpleTestCompatibility::isA($expected, 'SimpleExpectation')) {
                return $expected;
            }
            return new EqualExpectation($expected);
        }
        
        /**
         *    @deprecated
         */
        function assertErrorPattern($pattern, $message = "%s") {
            return $this->assertError(new PatternExpectation($pattern), $message);
        }
    }
?>
