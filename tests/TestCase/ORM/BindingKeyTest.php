<?php
declare(strict_types=1);

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
namespace Cake\Test\TestCase\ORM;

use Cake\TestSuite\TestCase;

/**
 * Integration tests for using the bindingKey in associations
 */
class BindingKeyTest extends TestCase
{
    /**
     * Fixture to be used
     *
     * @var array
     */
    protected $fixtures = [
        'core.AuthUsers',
        'core.SiteAuthors',
        'core.Users',
    ];

    /**
     * Data provider for the two types of strategies BelongsTo and HasOne implements
     *
     * @return array
     */
    public function strategiesProviderJoinable()
    {
        return [['join'], ['select']];
    }

    /**
     * Data provider for the two types of strategies HasMany and BelongsToMany implements
     *
     * @return array
     */
    public function strategiesProviderExternal()
    {
        return [['subquery'], ['select']];
    }

    /**
     * Tests that bindingKey can be used in belongsTo associations
     *
     * @dataProvider strategiesProviderJoinable
     * @return void
     */
    public function testBelongsto($strategy)
    {
        $users = $this->getTableLocator()->get('Users');
        $users->belongsTo('AuthUsers', [
            'bindingKey' => 'username',
            'foreignKey' => 'username',
            'strategy' => $strategy,
        ]);

        $result = $users->find()
            ->contain(['AuthUsers']);

        $expected = ['mariano', 'nate', 'larry', 'garrett'];
        $expected = array_combine($expected, $expected);
        $this->assertEquals(
            $expected,
            $result->combine('username', 'auth_user.username')->toArray()
        );

        $expected = [1 => 1, 2 => 5, 3 => 2, 4 => 4];
        $this->assertEquals(
            $expected,
            $result->combine('id', 'auth_user.id')->toArray()
        );
    }

    /**
     * Tests that bindingKey can be used in hasOne associations
     *
     * @dataProvider strategiesProviderJoinable
     * @return void
     */
    public function testHasOne($strategy)
    {
        $users = $this->getTableLocator()->get('Users');
        $users->hasOne('SiteAuthors', [
            'bindingKey' => 'username',
            'foreignKey' => 'name',
            'strategy' => $strategy,
        ]);

        $users->updateAll(['username' => 'jose'], ['username' => 'garrett']);
        $result = $users->find()
            ->contain(['SiteAuthors'])
            ->where(['username' => 'jose'])
            ->first();

        $this->assertEquals(3, $result->site_author->id);
    }

    /**
     * Tests that bindingKey can be used in hasOne associations
     *
     * @dataProvider strategiesProviderExternal
     * @return void
     */
    public function testHasMany($strategy)
    {
        $users = $this->getTableLocator()->get('Users');
        $authors = $users->hasMany('SiteAuthors', [
            'bindingKey' => 'username',
            'foreignKey' => 'name',
            'strategy' => $strategy,
        ]);

        $authors->updateAll(['name' => 'garrett'], ['id >' => 2]);
        $result = $users->find()
            ->contain(['SiteAuthors'])
            ->where(['username' => 'garrett']);

        $expected = [3, 4];
        $result = $result->extract('site_authors.{*}.id')->toList();
        $this->assertEquals($expected, $result);
    }
}
