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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Database\Exception\DatabaseException;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PDO;
use TestApp\Model\Entity\Article;
use TestApp\Model\Enum\ArticleStatus;
use TestApp\Model\Enum\Priority;

/**
 * Test for the String type.
 */
class EnumTypeTest extends TestCase
{
    /**
     * @inheritDoc
     */
    protected array $fixtures = ['core.Articles', 'core.FeaturedTags'];

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Original type map
     *
     * @var array
     */
    protected $_originalMap;

    /**
     * @var \Cake\Database\Type\EnumType
     */
    protected $stringType;

    /**
     * @var \Cake\Database\Type\EnumType
     */
    protected $intType;

    /**
     * @var \Cake\ORM\Table
     */
    protected $Articles;

    /**
     * @var \Cake\ORM\Table
     */
    protected $FeaturedTags;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->driver = ConnectionManager::get('test')->getDriver();

        $this->_originalMap = TypeFactory::getMap();
        $this->stringType = TypeFactory::build(EnumType::from(ArticleStatus::class));
        $this->intType = TypeFactory::build(EnumType::from(Priority::class));

        $this->Articles = $this->getTableLocator()->get('Articles');
        $this->Articles->getSchema()->setColumnType('published', EnumType::from(ArticleStatus::class));

        $this->FeaturedTags = $this->getTableLocator()->get('FeaturedTags');
        $this->FeaturedTags->getSchema()->setColumnType('priority', EnumType::from(Priority::class));
    }

    /**
     * Restores Type class state
     */
    public function tearDown(): void
    {
        parent::tearDown();

        TypeFactory::setMap($this->_originalMap);
    }

    /**
     * Check that 2nd argument must be a valid backed enum
     */
    public function testInvalidEnumClass(): void
    {
        $this->expectException(DatabaseException::class);
        new EnumType('invalid', Article::class);
    }

    /**
     * Check get enum class string
     */
    public function testGetEnumClassString(): void
    {
        $this->assertSame(ArticleStatus::class, $this->stringType->getEnumClassName());
        $this->assertSame(Priority::class, $this->intType->getEnumClassName());
    }

    /**
     * Test converting to database format with string backed enum
     */
    public function testToDatabaseString(): void
    {
        $this->assertNull($this->stringType->toDatabase(null, $this->driver));
        $this->assertSame('Y', $this->stringType->toDatabase(ArticleStatus::PUBLISHED, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->assertSame('Y', $this->stringType->toDatabase(ArticleStatus::PUBLISHED->value, $this->driver));
        $this->stringType->toDatabase([1, 2], $this->driver);
        $this->stringType->toDatabase(Priority::HIGH, $this->driver);
    }

    /**
     * Test converting to database format with integer backed enum
     */
    public function testToDatabaseInteger(): void
    {
        $this->assertNull($this->intType->toDatabase(null, $this->driver));
        $this->assertSame(3, $this->intType->toDatabase(Priority::HIGH, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->assertSame(3, $this->intType->toDatabase(Priority::HIGH->value, $this->driver));
        $this->intType->toDatabase('Y', $this->driver);
        $this->intType->toDatabase(ArticleStatus::PUBLISHED, $this->driver);
    }

    /**
     * Test toPHP with string backed enum
     */
    public function testToPHPString(): void
    {
        $this->assertNull($this->stringType->toPHP(null, $this->driver));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringType->toPHP('Y', $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->stringType->toPHP(1, $this->driver);
    }

    /**
     * Test toPHP with integer backed enum
     */
    public function testToPHPInteger(): void
    {
        $this->assertNull($this->intType->toPHP(null, $this->driver));
        $this->assertSame(Priority::HIGH, $this->intType->toPHP(3, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->intType->toPHP('N', $this->driver);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_STR, $this->stringType->toStatement('Y', $this->driver));
        $this->assertSame(PDO::PARAM_INT, $this->intType->toStatement(1, $this->driver));
    }

    /**
     * Test marshalling with string backed enum
     */
    public function testMarshalString(): void
    {
        $this->assertNull($this->stringType->marshal(null));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringType->marshal('Y'));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringType->marshal(ArticleStatus::PUBLISHED));
        $this->expectException(InvalidArgumentException::class);
        $this->stringType->marshal(1);
    }

    /**
     * Test marshalling with integer backed enum
     */
    public function testMarshalInteger(): void
    {
        $this->assertNull($this->intType->marshal(null));
        $this->assertSame(Priority::LOW, $this->intType->marshal(1));
        $this->assertSame(Priority::MEDIUM, $this->intType->marshal(Priority::MEDIUM));
        $this->expectException(InvalidArgumentException::class);
        $this->intType->marshal('Y');
    }

    /**
     * Check adding entity fields with a string backed enum instance
     */
    public function testDtringEnumField(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->Articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => ArticleStatus::PUBLISHED,
        ]);
        $saved = $this->Articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);

        $this->assertSame(ArticleStatus::PUBLISHED, $this->Articles->get(4)->published);
    }

    /**
     * Check adding entity fields with scalar value representing string backed enum
     */
    public function testStringEnumFieldWithBackingType(): void
    {
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $this->Articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => 'Y',
        ]);
        $saved = $this->Articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);

        $this->assertSame(ArticleStatus::PUBLISHED, $this->Articles->get(4)->published);
    }

    /**
     * Check adding entity fields with invalid scalar value sets error on field
     */
    public function testStringEnumFieldWithBackingTypeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->Articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => 'P',
        ]);
    }

    /**
     * Check adding entity fields with an integer backed enum instance
     */
    public function testIntEnumField(): void
    {
        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $this->FeaturedTags->newEntity([
            'priority' => Priority::MEDIUM,
        ]);
        $saved = $this->FeaturedTags->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(Priority::MEDIUM, $entity->priority);

        $this->assertSame(Priority::MEDIUM, $this->FeaturedTags->get(4)->priority);
    }

    /**
     * Check adding entity fields with scalar value representing integer backed enum
     */
    public function testIntEnumFieldWithBackingType(): void
    {
        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $this->FeaturedTags->newEntity([
            'priority' => 2,
        ]);
        $saved = $this->FeaturedTags->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(Priority::MEDIUM, $entity->priority);

        $this->assertSame(Priority::MEDIUM, $this->FeaturedTags->get(4)->priority);
    }

    /**
     * Check adding entity fields with invalid scalar value sets error on field
     */
    public function testIntEnumFieldWithBackingTypeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->FeaturedTags->newEntity([
            'priority' => -1,
        ]);
    }

    /**
     * Check updating an entity via an string enum instance
     */
    public function testUpdateEnumField(): void
    {
        $this->assertSame(ArticleStatus::PUBLISHED, $this->Articles->get(1)->published);

        $entity = $this->Articles->get(1);
        $entity->published = ArticleStatus::UNPUBLISHED;
        $this->Articles->save($entity);
        $this->assertSame(ArticleStatus::UNPUBLISHED, $entity->published);

        $this->assertSame(ArticleStatus::UNPUBLISHED, $this->Articles->get(1)->published);
    }
}
