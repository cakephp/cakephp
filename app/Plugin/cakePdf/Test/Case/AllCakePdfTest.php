<?php

class AllCakePdfTest extends PHPUnit_Framework_TestSuite {

/**
 * Suite define the tests for this suite
 *
 * @return void
 */
	public static function suite() {
		$suite = new CakeTestSuite('All CakePdf tests');

		$path = CakePlugin::path('CakePdf') . 'Test' . DS . 'Case' . DS;
		$suite->addTestDirectoryRecursive($path);
		return $suite;
	}
}
