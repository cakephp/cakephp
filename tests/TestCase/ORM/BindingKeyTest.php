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
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Integration tetss for table operations involving composite keys
 */
class BindingKeyTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.users',
        'core.auth_users'
    ];

    public function testBelongsto()
    {
        $users = TableRegistry::get('Users');
        $users->belongsTo('AuthUsers', [
            'bindingKey' => 'username',
            'foreignKey' => 'username'
        ]);

        $result = $users->find()
            ->contain(['AuthUsers']);
        debug($result);
        debug($result->toArray());
    }

}
