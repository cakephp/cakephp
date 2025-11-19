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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\ORM\Rule;

use Cake\ORM\Entity;
use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Rule\ExistsInNullable;
use Cake\TestSuite\TestCase;

/**
 * Tests the ExistsInNullable rule
 */
class ExistsInNullableTest extends TestCase
{
    /**
     * Fixtures to be loaded
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'core.SiteArticles',
        'core.SiteAuthors',
    ];

    /**
     * Test that allowNullableNulls is true by default
     */
    public function testAllowNullableNullsDefaultValue(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertInstanceOf(Entity::class, $table->save($entity));
    }

    /**
     * Test that allowNullableNulls can be explicitly overridden to false
     */
    public function testAllowNullableNullsCanBeOverridden(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors', [
            'allowNullableNulls' => false,
        ]));
        $this->assertFalse($table->save($entity));
    }

    /**
     * Test with all foreign keys set
     */
    public function testAllKeysSet(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 1,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertInstanceOf(Entity::class, $table->save($entity));
    }

    /**
     * Test with invalid foreign key
     */
    public function testInvalidKey(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(
            new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors'),
            '_existsIn',
            ['errorField' => 'author_id', 'message' => 'will error'],
        );
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->getErrors());
    }

    /**
     * Test with all invalid foreign keys
     */
    public function testInvalidKeys(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 99999999,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(
            new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors'),
            '_existsIn',
            ['errorField' => 'author_id', 'message' => 'will error'],
        );
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'will error']], $entity->getErrors());
    }

    /**
     * Test with saveMany
     */
    public function testSaveMany(): void
    {
        $entities = [
            new Entity([
                'id' => 1,
                'author_id' => null,
                'site_id' => 1,
                'name' => 'New Site Article without Author',
            ]),
            new Entity([
                'id' => 2,
                'author_id' => 1,
                'site_id' => 1,
                'name' => 'New Site Article with Author',
            ]),
        ];
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors', [
            'message' => 'will error with array_combine warning',
        ]));
        /** @var iterable<\Cake\ORM\Entity> $result */
        $result = $table->saveMany($entities);
        $this->assertCount(2, $result);
        /** @var array<\Cake\ORM\Entity> $result */
        $result = iterator_to_array($result);

        $this->assertInstanceOf(Entity::class, $result[0]);
        $this->assertEmpty($result[0]->getErrors());

        $this->assertInstanceOf(Entity::class, $result[1]);
        $this->assertEmpty($result[1]->getErrors());
    }

    /**
     * Test using ExistsInNullable directly with table object
     */
    public function testWithTableObject(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $authorsTable = $this->getTableLocator()->get('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(new ExistsInNullable(['author_id', 'site_id'], $authorsTable));
        $this->assertInstanceOf(Entity::class, $table->save($entity));
    }

    /**
     * Test with custom message
     */
    public function testCustomMessage(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 1,
            'name' => 'New Site Article with Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        $rules = $table->rulesChecker();

        $rules->add(
            new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors'),
            '_existsIn',
            ['errorField' => 'author_id', 'message' => 'Custom error message'],
        );
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'Custom error message']], $entity->getErrors());
    }

    /**
     * Test using rulesChecker existsInNullable method
     */
    public function testUsingRulesCheckerMethod(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => null,
            'site_id' => 1,
            'name' => 'New Site Article without Author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        /** @var \Cake\ORM\RulesChecker $rules */
        $rules = $table->rulesChecker();

        $rules->add($rules->existsInNullable(['author_id', 'site_id'], 'SiteAuthors'));
        $this->assertInstanceOf(Entity::class, $table->save($entity));
    }

    /**
     * Test using rulesChecker existsInNullable method with custom message
     */
    public function testUsingRulesCheckerMethodWithCustomMessage(): void
    {
        $entity = new Entity([
            'id' => 10,
            'author_id' => 99999999,
            'site_id' => 1,
            'name' => 'New Site Article with invalid author',
        ]);
        $table = $this->getTableLocator()->get('SiteArticles');
        $table->belongsTo('SiteAuthors');
        /** @var \Cake\ORM\RulesChecker $rules */
        $rules = $table->rulesChecker();

        $rules->add($rules->existsInNullable(['author_id', 'site_id'], 'SiteAuthors', 'Custom message via method'));
        $this->assertFalse($table->save($entity));
        $this->assertEquals(['author_id' => ['_existsIn' => 'Custom message via method']], $entity->getErrors());
    }

    /**
     * Test that ExistsInNullable extends ExistsIn
     */
    public function testExtendsExistsIn(): void
    {
        $rule = new ExistsInNullable(['author_id', 'site_id'], 'SiteAuthors');

        $this->assertInstanceOf(ExistsIn::class, $rule);
    }
}
