<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
//////////////////////////////////////////////////////////////////////////


/**
 * The includes below are required for the TestsController to work.
 */
uses('test', 'folder', 'inflector');

class TestsController extends TestsHelper {

/**
 * Runs all library and application tests
 *
 * @package cake
 * @subpackage cake.app
 */
	function test_all () 
	{
		$this->layout = 'test';

		$tests_folder = new Folder('../tests');

		$results = array();
		$total_errors = 0;
		foreach ($tests_folder->findRecursive('.*\.php') as $test) 
		{
			if (preg_match('/^(.+)\.php/i', basename($test), $r)) 
			{
				require_once($test);
				$test_name = Inflector::Camelize($r[1]);
				if (preg_match('/^(.+)Test$/i', $test_name, $r)) 
				{
					$module_name = $r[1];
				}
				else 
				{
					$module_name = $test_name;
				}
				$suite = new TestSuite($test_name);
				$result = TestRunner::run($suite);

				$total_errors += $result['errors'];

				$results[] = array
				(
					'name'=>$module_name, 
					'result'=>$result,
				);
			}
		}

		$this->set('success', !$total_errors);
		$this->set('results', $results);
	}
}

?>
