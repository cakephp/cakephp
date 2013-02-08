<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.templates
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>	</div>
		</div>
		<div id="footer">
			<p>
			<!--PLEASE USE ONE OF THE POWERED BY CAKEPHP LOGO-->
			<a href="http://www.cakephp.org/" target="_blank">
				<img src="<?php echo $baseDir; ?>img/cake.power.gif" alt="CakePHP(tm) :: Rapid Development Framework" /></a>
			</p>
		</div>
		<?php
			App::uses('View', 'View');
			$null = null;
			$View = new View($null, false);
			echo $View->element('sql_dump');
		?>
	</div>
</body>
</html>
