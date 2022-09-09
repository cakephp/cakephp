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

use Cake\Database\Driver;
use Cake\Database\Exception\DatabaseException;
use Cake\Database\Type\EnumType;
use Cake\Database\TypeFactory;
use Cake\ORM\Table;
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
     * @var \Cake\Database\TypeInterface|\Cake\Database\Type\EnumType
     */
    protected $stringtype;

    /**
     * @var \Cake\Database\TypeInterface|\Cake\Database\Type\EnumType
     */
    protected $integertype;

    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * Original type map
     *
     * @var array
     */
    protected $_originalMap = [];

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_originalMap = TypeFactory::getMap();
        $this->stringtype = TypeFactory::build(EnumType::from(ArticleStatus::class));
        $this->integertype = TypeFactory::build(EnumType::from(Priority::class));
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
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
    public function testGetEnum(): void
    {
        $this->assertSame(ArticleStatus::class, $this->stringtype->getEnumClassName());
        $this->assertSame(Priority::class, $this->integertype->getEnumClassName());
    }

    /**
     * Test converting to database format with string backed enum
     */
    public function testToDatabaseString(): void
    {
        $this->assertNull($this->stringtype->toDatabase(null, $this->driver));
        $this->assertSame('Y', $this->stringtype->toDatabase(ArticleStatus::PUBLISHED, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->assertSame('Y', $this->stringtype->toDatabase(ArticleStatus::PUBLISHED->value, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->stringtype->toDatabase([1, 2], $this->driver);
    }

    /**
     * Test converting to database format with integer backed enum
     */
    public function testToDatabaseInteger(): void
    {
        $this->assertNull($this->integertype->toDatabase(null, $this->driver));
        $this->assertSame(3, $this->integertype->toDatabase(Priority::HIGH, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->assertSame(3, $this->integertype->toDatabase(Priority::HIGH->value, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->integertype->toDatabase('Y', $this->driver);
    }

    /**
     * Test toPHP with string backed enum
     */
    public function testToPHPString(): void
    {
        $this->assertNull($this->stringtype->toPHP(null, $this->driver));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringtype->toPHP('Y', $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->stringtype->toPHP(1, $this->driver);
    }

    /**
     * Test toPHP with integer backed enum
     */
    public function testToPHPInteger(): void
    {
        $this->assertNull($this->integertype->toPHP(null, $this->driver));
        $this->assertSame(Priority::HIGH, $this->integertype->toPHP(3, $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->integertype->toPHP('N', $this->driver);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_INT, $this->stringtype->toStatement(1, $this->driver));
        $this->assertSame(PDO::PARAM_STR, $this->stringtype->toStatement('Y', $this->driver));
    }

    /**
     * Test marshalling with string backed enum
     */
    public function testMarshalString(): void
    {
        $this->assertNull($this->stringtype->marshal(null));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringtype->marshal('Y'));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->stringtype->marshal(ArticleStatus::PUBLISHED));
        $this->expectException(InvalidArgumentException::class);
        $this->stringtype->marshal(1);
    }

    /**
     * Test marshalling with integer backed enum
     */
    public function testMarshalInteger(): void
    {
        $this->assertNull($this->integertype->marshal(null));
        $this->assertSame(Priority::LOW, $this->integertype->marshal(1));
        $this->assertSame(Priority::MEDIUM, $this->integertype->marshal(Priority::MEDIUM));
        $this->expectException(InvalidArgumentException::class);
        $this->integertype->marshal('Y');
    }

    /**
     * Check adding entity fields with a string backed enum instance
     */
    public function testTableAddWithStringEnum(): void
    {
        $articles = $this->getArticlesTable();

        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => ArticleStatus::PUBLISHED,
        ]);
        $saved = $articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check adding entity fields with an integer backed enum instance
     */
    public function testTableAddWithIntegerEnum(): void
    {
        $featuredTags = $this->getFeaturedTagsTable();

        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $featuredTags->newEntity([
            'tag_id' => 1,
            'priority' => Priority::MEDIUM,
        ]);
        $saved = $featuredTags->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(Priority::MEDIUM, $entity->priority);
    }

    /**
     * Check adding entity fields with scalar value representing string backed enum
     */
    public function testTableAddWithScalarStringValue(): void
    {
        $articles = $this->getArticlesTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => 'Y',
        ]);
        $saved = $articles->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check adding entity fields with scalar value representing integer backed enum
     */
    public function testTableAddWithScalarIntegerValue(): void
    {
        $featuredTags = $this->getFeaturedTagsTable();
        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $featuredTags->newEntity([
            'tag_id' => 1,
            'priority' => 2,
        ]);
        $saved = $featuredTags->save($entity);
        $this->assertNotFalse($saved);
        $this->assertSame(Priority::MEDIUM, $entity->priority);
    }

    /**
     * Check adding entity fields with invalid scalar value sets error on field
     */
    public function testTableAddWithInvalidScalarStringValue(): void
    {
        $articles = $this->getArticlesTable();
        $this->expectException(InvalidArgumentException::class);
        $articles->newEntity([
            'author_id' => 1,
            'title' => 'My Title',
            'body' => 'My post',
            'published' => 'P',
        ]);
    }

    /**
     * Check adding entity fields with invalid scalar value sets error on field
     */
    public function testTableAddWithInvalidScalarIntegerValue(): void
    {
        $featuredTags = $this->getFeaturedTagsTable();
        $this->expectException(InvalidArgumentException::class);
        $featuredTags->newEntity([
            'tag_id' => 1,
            'priority' => -1,
        ]);
    }

    /**
     * Check to get an entity and automatically transform field to an string backed enum instance
     */
    public function testTableGetWithStringBackedEnum(): void
    {
        $articles = $this->getArticlesTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->get(1);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check to get an entity and automatically transform field to an integer backed enum instance
     */
    public function testTableGetWithIntegerBackedEnum(): void
    {
        $featuredTags = $this->getFeaturedTagsTable();
        /** @var \Cake\Datasource\EntityInterface $entity */
        $entity = $featuredTags->get(1);
        $this->assertSame(Priority::MEDIUM, $entity->priority);
    }

    /**
     * Check updating an entity via an string enum instance
     */
    public function testTableUpdateWithStringEnum(): void
    {
        $articles = $this->getArticlesTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->get(1);
        $entity->published = ArticleStatus::UNPUBLISHED;
        $articles->save($entity);
        $this->assertSame(ArticleStatus::UNPUBLISHED, $entity->published);
    }

    /**
     * Check updating an entity via an integer backed enum instance
     */
    public function testTableUpdateWithIntegerEnum(): void
    {
        $featuredTags = $this->getFeaturedTagsTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $featuredTags->get(1);
        $entity->priority = Priority::HIGH;
        $featuredTags->save($entity);
        $this->assertSame(Priority::HIGH, $entity->priority);
    }

    private function getArticlesTable(): Table
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->setColumnType('published', EnumType::from(ArticleStatus::class));

        return $articles;
    }

    private function getFeaturedTagsTable(): Table
    {
        $featuredTags = $this->getTableLocator()->get('FeaturedTags');
        $featuredTags->getSchema()->setColumnType('priority', EnumType::from(Priority::class));

        return $featuredTags;
    }
}
