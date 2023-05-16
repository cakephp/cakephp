<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
use Cake\ORM\TableRegistry;

$autoTables = TableRegistry::getTableLocator()->genericInstances();
if (!$autoTables) {
    return;
}
?>
<h3>Could this be caused by using Auto-Tables?</h3>
<p>
Some of the Table objects in your application were created by instantiating "<strong>Cake\ORM\Table</strong>"
instead of any other specific subclass.
</p>
<p>This could be the cause for this exception. Auto-Tables are created for you under the following circumstances:</p>
<ul>
    <li>The class for the specified table does not exist.</li>
    <li>The Table was created with a typo: <strong><em>$this->getTableLocator()->get('Articles');</em></strong></li>
    <li>The class file has a typo in the name or incorrect namespace: <strong><em>class Articles extends Table</em></strong></li>
    <li>The file containing the class has a typo or incorrect casing: <strong><em>Articles.php</em></strong></li>
    <li>The Table was used using associations but the association has a typo: <strong><em>$this->belongsTo('Articles')</em></strong></li>
    <li>The table class resides in a Plugin but <strong><em>no plugin notation</em></strong> was used in the association definition.</li>
</ul>
<br>
<p>Please try correcting the issue for the following table aliases:</p>
<ul>
<?php foreach ($autoTables as $alias => $table) : ?>
    <li><strong><?= $alias ?></strong></li>
<?php endforeach; ?>
</ul>
<br>
