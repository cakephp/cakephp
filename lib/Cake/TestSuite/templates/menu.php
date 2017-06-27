<?php
// @codingStandardsIgnoreFile
/**
 * Short description for file.
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.TestSuite.templates
 * @since         CakePHP(tm) v 1.2.0.4433
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
?>
<div class="test-menu">
<ul>
	<li>
		<span style="font-size: 18px">App</span>
		<ul>
			<li><a href='<?php echo $cases; ?>'>Tests</a></li>
		</ul>
	</li>
<?php if (!empty($plugins)): ?>
	<li style="padding-top: 10px">
		<span style="font-size: 18px">Plugins</span>
	<?php foreach ($plugins as $plugin) : ?>
			<ul>
				<li style="padding-top: 10px">
					<span  style="font-size: 18px"><?php echo $plugin; ?></span>
					<ul>
						<li><?php printf('<a href="%s&amp;plugin=%s">Tests</a>', $cases, $plugin); ?></li>
					</ul>
				</li>
			</ul>
	<?php endforeach; ?>
	</li>
<?php endif; ?>
	<li style="padding-top: 10px">
		<span style="font-size: 18px">Core</span>
		<ul>
			<li><a href='<?php echo $cases; ?>&amp;core=true'>Tests</a></li>
		</ul>
	</li>
</ul>
</div>
<div  class="test-results">
