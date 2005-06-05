<?PHP
//////////////////////////////////////////////////////////////////////////
// + $Id$
// +------------------------------------------------------------------+ //
// + Cake <https://developers.nextco.com/cake/>                       + //
// + Copyright: (c) 2005, Cake Authors/Developers                     + //
// + Author(s): Michal Tatarynowicz aka Pies <tatarynowicz@gmail.com> + //
// +            Larry E. Masters aka PhpNut <nut@phpnut.com>          + //
// +            Kamil Dzielinski aka Brego <brego.dk@gmail.com>       + //
// +------------------------------------------------------------------+ //
// + Licensed under The MIT License                                   + //
// + Redistributions of files must retain the above copyright notice. + //
// + See: http://www.opensource.org/licenses/mit-license.php          + //
//////////////////////////////////////////////////////////////////////////

/**
 *  
 * 
 *
 * @filesource 
 * @author Cake Authors/Developers
 * @copyright Copyright (c) 2005, Cake Authors/Developers
 * @link https://developers.nextco.com/cake/wiki/Authors Authors/Developers
 * @package cake
 * @subpackage cake.app.controllers
 * @since Cake v 1.0.0.158
 * @version $Revision$
 * @modifiedby $LastChangedBy$
 * @lastmodified $Date$
 * @license http://www.opensource.org/licenses/mit-license.php The MIT License
 */

/**
 * Description:
 * 
 */
uses('test', 'folder', 'inflector');

/**
 * 
 * 
 *
 * @package cake
 * @subpackage cake.app.controllers
 * @since Cake v 1.0.0.158
 *
 */
class TestsController extends TestsHelper {

/**
 *  Runs all library and application tests
 */
	function test_all () {

		$this->layout = 'test';

		$tests_folder = new Folder('../tests');

		$results = array();
		$total_errors = 0;
		foreach ($tests_folder->findRecursive('.*\.php') as $test) {
			if (preg_match('/^(.+)\.php/i', basename($test), $r)) {
				require_once($test);
				$test_name = Inflector::Camelize($r[1]);
				if (preg_match('/^(.+)Test$/i', $test_name, $r)) {
					$module_name = $r[1];
				}
				else {
					$module_name = $test_name;
				}
				$suite = new TestSuite($test_name);
				$result = TestRunner::run($suite);

				$total_errors += $result['errors'];

				$results[] = array(
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
