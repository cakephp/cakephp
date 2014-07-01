<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         0.10.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Routing\Router;
use Cake\Utility\Debugger;

?>
<h2>Missing Route</h2>
<p class="error">
	<strong>Error: </strong>
	<?= $error->getMessage(); ?>
</p>

<h3>Connected Routes</h3>
<?php
foreach (Router::routes() as $scope):
	printf('<h4>Scope: %s</h4>', $scope->path());
	echo '<table cellspacing="0" cellpadding="0">';
	echo '<tr><th>Template</th><th>Defaults</th><th>Options</th></tr>';

	foreach ($scope->routes() as $route):
		echo '<tr>';
		printf(
			'<th width="25%%">%s</th><th>%s</th><th width="20%%">%s</th>',
			$route->template,
			Debugger::exportVar($route->defaults),
			Debugger::exportVar($route->options)
		);
		echo '</tr>';
	endforeach;
	echo '</table>';
endforeach;
?>
<p class="notice">
	<strong>Notice: </strong>
	<?= sprintf('If you want to customize this error message, create %s', APP_DIR . DS . 'Template' . DS . 'Error' . DS . 'missing_route.ctp'); ?>
</p>
<?= $this->element('exception_stack_trace'); ?>
