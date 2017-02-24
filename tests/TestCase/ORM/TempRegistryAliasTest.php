<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Cake\Test\TestCase\ORM;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use function debug;

/**
 * Description of TempRegistryAliasTest
 *
 * @author Robert Pustułka <r.pustulka@robotusers.com>
 */
class TempRegistryAliasTest extends TestCase
{

    /**
     * Fixture to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.articles_tags',
        'core.authors',
        'core.comments',
        'core.datatypes',
        'core.posts',
        'core.tags'
    ];

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        TableRegistry::clear();
    }

    /**
     * Test finding fields on the non-default table that
     * have the same name as the primary table.
     *
     * @return void
     */
    public function testContainWithRegistryAliasBelongsTo()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsTo('Foo', [
            'registryAlias' => 'Authors'
        ]);

        $query = $table->find()
            ->contain(['Foo']);

        $entity = $query->first();
        $this->assertNotEmpty($entity->foo);
    }

    /**
     * Test finding fields on the non-default table that
     * have the same name as the primary table.
     *
     * @return void
     */
    public function testContainWithRegistryAliasHasMany()
    {
        $table = TableRegistry::get('Authors');
        $table->hasMany('Foo', [
            'registryAlias' => 'Articles'
        ]);

        $query = $table->find()
            ->contain(['Foo']);

        $entity = $query->first();
        $this->assertNotEmpty($entity->foo);
    }

    /**
     * Test finding fields on the non-default table that
     * have the same name as the primary table.
     *
     * @return void
     */
    public function testContainWithRegistryAliasBelongsToMany()
    {
        $table = TableRegistry::get('Articles');
        $table->belongsToMany('Foo', [
            'registryAlias' => 'Tags'
        ]);

        $query = $table->find()
            ->contain(['Foo']);

        $entity = $query->first();
        $this->assertNotEmpty($entity->foo);
    }
}