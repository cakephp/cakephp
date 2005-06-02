<?PHP
////////////////////////////////////////////////////////////////////////// //
// + $Id$
// +---------------------------------------------------------------------+ //
// + PHP framework for testing, based on the design of "JUnit".          + //
// + Copyright (c) 2000 Fred Yankowski                                   + //
// +                                                                     + //
// + Written by Fred Yankowski <fred@ontosys.com>                        + //
// +            OntoSys, Inc  <http://www.OntoSys.com>                   + //
// +                                                                     + //
// + Changes by Michal Tatarynowicz <tatarynowicz@gmail.com>             + //
// +---------------------------------------------------------------------+ //
// + Permission is hereby granted, free of charge, to any person         + //
// + obtaining a copy of this software and associated documentation      + //
// + files (the "Software"), to deal in the Software without             + //
// + restriction, including without limitation the rights to use, copy,  + //
// + modify, merge, publish, distribute, sublicense, and/or sell copies  + //
// + of the Software, and to permit persons to whom the Software is      + //
// + furnished to do so, subject to the following conditions:            + //
// +                                                                     + //
// + The above copyright notice and this permission notice shall be      + //
// + included in all copies or substantial portions of the Software.     + //
// +                                                                     + //
// + THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,     + //
// + EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF  + //
// + MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND               + //
// + NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS + //
// + BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN  + //
// + ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN   + //
// + CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE    + //
// + SOFTWARE.                                                           + //
/////////////////////////////////////////////////////////////////////////////

/**
  * Purpose: PHP framework for testing, based on the design of "JUnit".
  *
  * @filesource 
  * @author Fred Yankowski
  * @copyright Copyright (c) 2000 Fred Yankowski
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  * @version $Revision$
  * @modifiedby $LastChangedBy$
  * @lastmodified $Date$
  */

/**
  * Outputs given string.
  *
  * @param string $msg
  */
function trace($msg) {
	return;
	print($msg);
	flush();
}
/**
  * Enter description here...
  *
  */
if (phpversion() >= '4') {
/**
  * Enter description here...
  *
  * @param unknown_type $errno Error number
  * @param string $errstr Error string
  * @param unknown_type $errfile Error filename
  * @param unknown_type $errline Error on line
  */
	  function PHPUnit_error_handler($errno, $errstr, $errfile, $errline) {
	global $PHPUnit_testRunning;
	$PHPUnit_testRunning[0]->fail("<B>PHP ERROR:</B> ".$errstr." <B>in</B> ".$errfile." <B>at line</B> ".$errline);
	  }
}
/**
  * Exception class for testing
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestException {
	  /* Emulate a Java exception, sort of... */
/**
  * Message of the exception
  *
  * @var string
  */
	var $message;
/**
 * Type of exception
 *
 * @var string
 */
	var $type;
/**
  * Constructor.
  *
  * @param string $message
  * @param string $type
  * @return TestException
  */
	function TestException($message, $type = 'FAILURE') {
	  $this->message = $message;
	  $this->type = $type;
	}
/**
  * Returns the exception's message.
  *
  * @return string Message of exception
  */
	function getMessage() {
	  return $this->message;
	}
/**
  * Returns the exception's type.
  *
  * @return string Type of exception
  */
	function getType() {
	  return $this->type;
	}
}

/**
  * Asserts for Unit Testing
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class Assert {
/**
  * Enter description here...
  *
  * @param boolean $boolean
  * @param string $message
  */
	function assert($boolean, $message=0) {
	  if (! $boolean)
		$this->fail($message);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $expected
  * @param unknown_type $actual
  * @param unknown_type $message
  * @return unknown
  */
	function asEq ($expected, $actual, $message=0) {
		return $this->assertEquals($expected, $actual, $message);
	}
/**
  * Enter description here...
  *
  * @param unknown_type $expected
  * @param unknown_type $actual
  * @param unknown_type $message
  */
	function assertEquals($expected, $actual, $message=0) {
		if (gettype($expected) != gettype($actual)) {
			$this->failNotEquals($expected, $actual, "expected", $message);
			return;
		}

		if (phpversion() < '4') {
			if (is_object($expected) or is_object($actual) or is_array($expected) or is_array($actual)) {
				$this->error("INVALID TEST: cannot compare arrays or objects in PHP3");
				return;
			}
		}

		if (phpversion() >= '4' && is_object($expected)) {
			if (get_class($expected) != get_class($actual)) {
				$this->failNotEquals($expected, $actual, "expected", $message);
				return;
			}

			if (method_exists($expected, "equals")) {
				if (! $expected->equals($actual)) {
					$this->failNotEquals($expected, $actual, "expected", $message);
				}
				return;		// no further tests after equals()
			}
		}

		if (phpversion() >= '4.0.4') {
			if (is_null($expected) != is_null($actual)) {
				$this->failNotEquals($expected, $actual, "expected", $message);
				return;
			}
		}

		if ($expected != $actual) {
			$this->failNotEquals($actual, $expected, "expected", $message);
		}
	}

/**
  * Assert regular expression
  *
  * @param string $regexp
  * @param string $actual
  * @param unknown_type $message
  */
	function assertRegexp($regexp, $actual, $message=false) {
	  if (! preg_match($regexp, $actual)) {
		$this->failNotEquals($regexp, $actual, "pattern", $message);
	  }
	}

/**
  * Enter description here...
  *
  * @param unknown_type $string0
  * @param unknown_type $string1
  * @param unknown_type $message
  */
	function assertEqualsMultilineStrings($string0, $string1,
	$message="") {
	  $lines0 = split("\n",$string0);
	  $lines1 = split("\n",$string1);
	  if (sizeof($lines0) != sizeof($lines1)) {
		$this->failNotEquals(sizeof($lines0)." line(s)",
		                     sizeof($lines1)." line(s)", "expected", $message);
	  }
	  for($i=0; $i< sizeof($lines0); $i++) {
		$this->assertEquals(trim($lines0[$i]),
		                    trim($lines1[$i]),
		                    "line ".($i+1)." of multiline strings differ. ".$message); 
	  }
	}

/**
  * Enter description here...
  *
  * @param unknown_type $fn
  */
	function assertFileExists ($fn) {
		if (!file_exists($fn))
			$this->failNotEquals($fn, "file not found", "file expected");
	}

/**
  * Enter description here...
  *
  * @param unknown_type $fn
  * @param unknown_type $str
  */
	function assertFileContains ($fn, $str) {
		if (file_exists($fn)) {
			$lines = file($fn);
			$text = implode("\n", $lines);

			if (!preg_match("/{$str}/", $text))
				$this->failNotEquals($fn, 'expected '.$str, 'expected');
		}
		else
			$this->failNotEquals($fn, 'file doesn\'t exist', 'expected');
	}

/**
  * Enter description here...
  *
  * @param unknown_type $path
  */
	function assertDirExists ($path) {
		if (!is_dir($path))
			$this->failNotEquals($path, "directory not found", "expected");
	}

/**
  * Enter description here...
  *
  * @param unknown_type $value
  */
	function assertTrue ($value) {
		if (!$value)
			$this->failNotEquals($value, true, "expected");
	}


/**
  * Enter description here...
  *
  * @param mixed $value
  * @return string
  */
	function _formatValue ($value) {
		
		if (is_object($value) && method_exists($value, "toString")) {
			$valueStr = $value->toString();
		}
		elseif (is_bool($value)) {
			$valueStr = $value? 'false': 'true';
		}
		elseif (is_object($value) || is_array($value)) {
			$valueStr = serialize($value);
		}
		else {
			$valueStr = str_replace('<', '&lt;', str_replace('>', '&gt;', $value));
		}
		
		return array($valueStr, gettype($value));
	}


/**
  * Enter description here...
  *
  * @param unknown_type $expected
  * @param unknown_type $actual
  * @param unknown_type $expected_label
  * @param unknown_type $message
  */
	function failNotEquals ($expected, $actual, $expected_label, $message=null) {
		$out = array(
			'message'=>$message,
			'label'=>$expected_label,
			'expected'=>$this->_formatValue($expected, "expected"),
			'actual'=>$this->_formatValue($actual, "actual")
		);

		$this->fail($out);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $expected
  * @param unknown_type $actual
  * @param unknown_type $expected_label
  * @param unknown_type $message
  */
	function old_failNotEquals($expected, $actual, $expected_label, $message=0) {
	  // Private function for reporting failure to match.
	  $str = $message ? ($message . ' ') : '';
	  //$str .= "($expected_label/actual)<br>";
	  $str .= "<br>";
	  $str .= sprintf("%s<br>%s",
		    $this->_formatValue($expected, "expected"),
		    $this->_formatValue($actual, "actual"));
	  $this->fail($str);
	}
}

/**
  * Enter description here...
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestCase extends Assert /* implements Test */ {
	/* Defines context for running tests.  Specific context -- such as
	   instance variables, global variables, global state -- is defined
	   by creating a subclass that specializes the setUp() and
	   tearDown() methods.  A specific test is defined by a subclass
	   that specializes the runTest() method. */
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fName;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fClassName;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fResult;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fExceptions = array();

/**
 * Enter description here...
 *
 * @param unknown_type $name
 * @return TestCase
 */
	function TestCase($name) {
	  $this->fName = $name;
	}

/**
 * Enter description here...
 *
 * @param unknown_type $testResult
 * @return unknown
 */
	function run($testResult=0) {
	  /* Run this single test, by calling the run() method of the
		 TestResult object which will in turn call the runBare() method
		 of this object.  That complication allows the TestResult object
		 to do various kinds of progress reporting as it invokes each
		 test.  Create/obtain a TestResult object if none was passed in.
		 Note that if a TestResult object was passed in, it must be by
		 reference. */
	  if (! $testResult)
		$testResult = $this->_createResult();
	  $this->fResult = $testResult;
	  $testResult->run(&$this);
	  $this->fResult = 0;
	  return $testResult;
	}
	
/**
 * Enter description here...
 *
 * @return unknown
 */
	function classname() {
	  if (isset($this->fClassName)) {
		return $this->fClassName;
	  } else {
		return get_class($this);
	  }
	}

/**
 * Enter description here...
 *
 * @return unknown
 */
	function countTestCases() {
	  return 1;
	}

/**
 * Enter description here...
 *
 */
	function runTest() {

		if (version_compare(phpversion(), '4') >= 0) {
			global $PHPUnit_testRunning;
			eval('$PHPUnit_testRunning[0] = & $this;');

			// Saved ref to current TestCase, so that the error handler
			// can access it.  This code won't even parse in PHP3, so we
			// hide it in an eval.

			$old_handler = set_error_handler("PHPUnit_error_handler");
			// errors will now be handled by our error handler
		}

		$name = $this->name();
		if ((version_compare(phpversion(), '4') >= 0) && ! method_exists($this, $name)) {
		    $this->error("Method '$name' does not exist");
		}
		else
		    $this->$name();

		if (version_compare(phpversion(), '4') >= 0) {
			restore_error_handler(); // revert to prior error handler
			$PHPUnit_testRunning = null;
		}
	}

/**
 * Enter description here...
 *
 */
	function setUp() /* expect override */ {
	  //print("TestCase::setUp()<br>\n");
	}

/**
 * Enter description here...
 *
 */
	function tearDown() /* possible override */ {
	  //print("TestCase::tearDown()<br>\n");
	}

	////////////////////////////////////////////////////////////////


/**
 * Enter description here...
 *
 * @return unknown
  */
	function _createResult() /* protected */ {
	  /* override this to use specialized subclass of TestResult */
	  return new TestResult;
	}

/**
 * Enter description here...
 *
 * @param unknown_type $message
 */
	function fail($message=0) {
	  //printf("TestCase::fail(%s)<br>\n", ($message) ? $message : '');
	  /* JUnit throws AssertionFailedError here.  We just record the
		 failure and carry on */
	  $this->fExceptions[] = new TestException(&$message, 'FAILURE');
	}

/**
 * Enter description here...
 *
 * @param unknown_type $message
 */
	function error($message) {
	  /* report error that requires correction in the test script
		 itself, or (heaven forbid) in this testing infrastructure */
	  $this->fExceptions[] = new TestException(&$message, 'ERROR');
	  $this->fResult->stop();	// [does not work]
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function failed() {
		reset($this->fExceptions);
		while (list($key, $exception) = each($this->fExceptions)) {
	  if ($exception->type == 'FAILURE')
		  return true;
		}
		return false;
	}
/**
  * Enter description here...
  *
  * @return unknown
  */
	function errored() {
		reset($this->fExceptions);
		while (list($key, $exception) = each($this->fExceptions)) {
	  if ($exception->type == 'ERROR')
		  return true;
		}
		return false;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getExceptions() {
	  return $this->fExceptions;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function name() {
	  return $this->fName;
	}

/**
  * Enter description here...
  *
  */
	function runBare() {
	  $this->setup();
	  $this->runTest();
	  $this->tearDown();
	}
}


/**
  * Test Suite for Unit Testing.
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestSuite /* implements Test */ {
	/* Compose a set of Tests (instances of TestCase or TestSuite), and
	   run them all. */
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fTests = array();
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fClassname;

/**
  * Enter description here...
  *
  * @param unknown_type $classname
  * @return TestSuite
  */
	function TestSuite($classname=false) {
	  // Find all methods of the given class whose name starts with
	  // "test" and add them to the test suite.

	  // PHP3: We are just _barely_ able to do this with PHP's limited
	  // introspection...  Note that PHP seems to store method names in
	  // lower case, and we have to avoid the constructor function for
	  // the TestCase class superclass.  Names of subclasses of TestCase
	  // must not start with "Test" since such a class will have a
	  // constructor method name also starting with "test" and we can't
	  // distinquish such a construtor from the real test method names.
	  // So don't name any TestCase subclasses as "Test..."!

	  // PHP4:  Never mind all that.  We can now ignore constructor
	  // methods, so a test class may be named "Test...".

	  if (empty($classname))
		return;
	  $this->fClassname = $classname;

	if (!class_exists($classname)) {
		user_error('Tested class '.$classname.' doesn\'t appear to exist.', E_USER_WARNING);
		return;
	}

	 if (floor(phpversion()) >= 4) {
		// PHP4 introspection, submitted by Dylan Kuhn

		$names = get_class_methods($classname);

		while (list($key, $method) = each($names)) {
		  if (preg_match('/^test/', $method)) {
		    $test = new $classname($method);
		    if (strcasecmp($method, $classname) == 0 || is_subclass_of($test, $method)) {
		      // Ignore the given method name since it is a constructor:
		      // it's the name of our test class or it is the name of a
		      // superclass of our test class.  (This code smells funny.
		      // Anyone got a better way?)

		      //print "skipping $method<br>";
		    }
		    else {
		      $this->addTest($test);
		    }
		  }
		}
	  }
	  else {  // PHP3
		$dummy = new $classname("dummy");
		$names = (array) $dummy;
		while (list($key, $value) = each($names)) {
		  $type = gettype($value);
		  if ($type == "user function" && preg_match('/^test/', $key)
		  && $key != "testcase") {  
		    $this->addTest(new $classname($key));
		  }
		}
	  }
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function addTest($test) {
	  /* Add TestCase or TestSuite to this TestSuite */
	  $this->fTests[] = $test;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $testResult
  */
	function run(&$testResult) {
	  /* Run all TestCases and TestSuites comprising this TestSuite,
		 accumulating results in the given TestResult object. */
	  reset($this->fTests);
	  while (list($na, $test) = each($this->fTests)) {
		if ($testResult->shouldStop())
	break;
		$test->run(&$testResult);
	  }
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function countTestCases() {
	  /* Number of TestCases comprising this TestSuite (including those
		 in any constituent TestSuites) */
	  $count = 0;
	  reset($fTests);
	  while (list($na, $test_case) = each($this->fTests)) {
		$count += $test_case->countTestCases();
	  }
	  return $count;
	}
}


/**
  * Test Failure for Unit Testing
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestFailure {
	/* Record failure of a single TestCase, associating it with the
	   exception that occurred */
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fFailedTestName;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fException;

/**
  * Enter description here...
  *
  * @param unknown_type $test
  * @param unknown_type $exception
  * @return TestFailure
  */
	function TestFailure(&$test, &$exception) {
	  $this->fFailedTestName = $test->name();
	  $this->fException = $exception;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getExceptions() {
		// deprecated
		return array($this->fException);
	}
/**
  * Enter description here...
  *
  * @return unknown
  */
	function getException() {
		return $this->fException;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getTestName() {
	  return $this->fFailedTestName;
	}
}


/**
  * Test Result for Unit Testing
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestResult {
	/* Collect the results of running a set of TestCases. */
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fFailures = array();
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fErrors = array();
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fRunTests = 0;
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $fStop = false;

/**
  * Enter description here...
  *
  * @return TestResult
  */
	function TestResult() { }

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function _endTest($test) /* protected */ {
		/* specialize this for end-of-test action, such as progress
	 reports  */
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  * @param unknown_type $exception
  */
	function addError($test, $exception) {
		$this->fErrors[] = new TestFailure(&$test, &$exception);
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  * @param unknown_type $exception
  */
	function addFailure($test, $exception) {
		$this->fFailures[] = new TestFailure(&$test, &$exception);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function getFailures() {
	  return $this->fFailures;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function run($test) {
	  /* Run a single TestCase in the context of this TestResult */
	  $this->_startTest($test);
	  $this->fRunTests++;

	  $test->runBare();

	  /* this is where JUnit would catch AssertionFailedError */
	  $exceptions = $test->getExceptions();
	  reset($exceptions);
	  while (list($key, $exception) = each($exceptions)) {
	if ($exception->type == 'ERROR')
		$this->addError($test, $exception);
	else if ($exception->type == 'FAILURE')
		$this->addFailure($test, $exception);
	  }
	  //    if ($exceptions)
	  //      $this->fFailures[] = new TestFailure(&$test, &$exceptions);
	  $this->_endTest($test);
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function countTests() {
	  return $this->fRunTests;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function shouldStop() {
	  return $this->fStop;
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function _startTest($test) /* protected */ {
		/* specialize this for start-of-test actions */
	}

/**
  * Enter description here...
  *
  */
	function stop() {
	  /* set indication that the test sequence should halt */
	  $fStop = true;
	}

/**
  * Enter description here...
  *
  * @return unknown
  */
	function errorCount() {
		return count($this->fErrors);
	}
/**
  * Enter description here...
  *
  * @return unknown
  */
	function failureCount() {
		return count($this->fFailures);
	}
/**
  * Enter description here...
  *
  * @return unknown
  */
	function countFailures() {
		// deprecated
		return $this->failureCount();
	}
}

/**
  * Mines data from test results. Used for Unit Testing.
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class ResultDataMiner extends TestResult {
/**
  * Enter description here...
  *
  * @var unknown_type
  */
	var $tests = null;
/**
  * Total number of tests
  *
  * @var int
  */
	var $total_tests = 0;
/**
  * Total number of errors
  *
  * @var int
  */
	var $total_errors = 0;

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function _startTest($test) {
	}

/**
  * Enter description here...
  *
  * @param unknown_type $test
  */
	function _endTest($test) {
		$class_name = $test->classname();
		if (preg_match('/^(.*)test$/i', $class_name, $r))
			$class_name = $r[1];
		$method_name = $test->name();
		if (preg_match('/^test(.*)$/i', $method_name, $r))
			$method_name = $r[1];

		$errors = null;
		foreach ($test->getExceptions() as $exception) {
			$errors[] = $exception->message;
		}

		$this->tests[] = array(
			'class' => $class_name,
			'method' => $method_name,
			'failed' => $test->failed(),
			'errors' => $errors
		);

		$this->total_tests++;
		if ($test->failed()) $this->total_errors++;
	}

/**
  * Returns an array with total number of performed tests, total number of errors, and details of the tests.
  *
  * @return array Array with information on how the tests went. 
  */
	function report () {
		return array('tests'=>$this->total_tests, 'errors'=>$this->total_errors, 'details'=>$this->tests);
	}

}

/**
  * Test Runner for Unit Testing.
  *
  *
  * @package cake
  * @subpackage cake.libs
  * @since Cake v 1.0.0.0
  *
  */
class TestRunner {
/**
  * Run a suite of tests and report results.
  *
  * @param unknown_type $suite
  * @return unknown
  */
	function run($suite) {
		$result = new ResultDataMiner;
		$suite->run($result);
		return $result->report();
	}
}

?>