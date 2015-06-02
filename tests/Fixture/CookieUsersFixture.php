<?php
/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.2.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Class UserFixture
 *
 */
class CookieUsersFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'user_name' => ['type' => 'string', 'null' => false],
        'email' => ['type' => 'string', 'null' => false],
        'password' => ['type' => 'string', 'null' => false],
        'token' => ['type' => 'string', 'null' => false],
        'created' => 'datetime',
        'updated' => 'datetime',
        'uuid' => ['type' => 'string', 'null' => false],
        'remember_me_token' => ['type' => 'string', 'null' => false],
        'remember_me_token_created' => 'datetime',
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]]
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        [
            'user_name' => 'mariano',
            'email' => 'mariano@example.com',
            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
            'token' => '12345', 'created' => '2007-03-17 01:16:23',
            'updated' => '2007-03-17 01:18:31',
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2',
            'remember_me_token_created' => '2015-05-31 16:01:03'
        ],
        [
            'user_name' => 'nate',
            'email' => 'nate@example.com',
            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
            'token' => '23456',
            'created' => '2007-03-17 01:18:23',
            'updated' => '2007-03-17 01:20:31',
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2',
            'remember_me_token_created' => '2015-03-31 16:01:03'
        ],
        [
            'user_name' => 'larry',
            'email' => 'larry@example.com',
            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
            'token' => '34567',
            'created' => '2007-03-17 01:20:23',
            'updated' => '2007-03-17 01:22:31',
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2',
            'remember_me_token_created' => '2015-03-31 16:01:03'
        ],
        [
            'user_name' => 'garrett',
            'email' => 'garrett@example.com',
            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
            'token' => '45678',
            'created' => '2007-03-17 01:22:23',
            'updated' => '2007-03-17 01:24:31',
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2',
            'remember_me_token_created' => '2015-03-31 16:01:03'
        ],
        [
            'user_name' => 'chartjes',
            'email' => 'chartjes@example.com',
            'password' => '5f4dcc3b5aa765d61d8327deb882cf99',
            'token' => '56789',
            'created' => '2007-03-17 01:22:23',
            'updated' => '2007-03-17 01:24:31',
            'uuid' => 'e99a6234-22d0-4676-b4e1-4c58b9c937d5',
            'remember_me_token' => 'a4e4243a-946f-44b4-8250-886c4e068de2',
            'remember_me_token_created' => '2015-03-31 16:01:03'
        ]
    ];
}
