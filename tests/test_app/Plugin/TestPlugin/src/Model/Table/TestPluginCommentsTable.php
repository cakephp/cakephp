<?php
/**
 * CakePHP :  Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakefoundation.org/projects/info/cakephp CakePHP Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestPlugin\Model\Table;

use Cake\ORM\Table;

/**
 * Class TestPluginCommentsTable
 *
 */
class TestPluginCommentsTable extends Table
{

    public function initialize(array $config)
    {
        $this->table('test_plugin_comments');
    }
}
