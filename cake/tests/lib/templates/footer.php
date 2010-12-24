<?php
/**
 * Short description for file.
 *
 * PHP 5
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/view/1196/Testing>
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/view/1196/Testing CakePHP(tm) Tests
 * @package       cake.tests.lib
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
			App::import('Core', 'View');
			$null = null;
			$View = new View($null, false);
			echo $View->element('sql_dump');
		?>
	</div>
</body>
</html>