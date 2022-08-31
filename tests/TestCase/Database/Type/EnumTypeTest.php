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

/**
 * Test for the String type.
 */
class EnumTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\TypeInterface|\Cake\Database\Type\EnumType
     */
    protected $type;

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
        $typeName = EnumType::for(ArticleStatus::class);
        $this->type = TypeFactory::build($typeName);
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
        $this->assertSame(ArticleStatus::class, $this->type->getEnumClassName());
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $this->assertNull($this->type->toDatabase(null, $this->driver));
        $this->assertSame('Y', $this->type->toDatabase(ArticleStatus::PUBLISHED, $this->driver));
        $this->assertSame('Y', $this->type->toDatabase(ArticleStatus::PUBLISHED->value, $this->driver));
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->type->toPHP('Y', $this->driver));
        $this->expectException(InvalidArgumentException::class);
        $this->type->toPHP(1, $this->driver);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_INT, $this->type->toStatement(1, $this->driver));
        $this->assertSame(PDO::PARAM_STR, $this->type->toStatement('Y', $this->driver));
    }

    /**
     * Test marshalling
     */
    public function testMarshal(): void
    {
        $this->assertNull($this->type->marshal(null));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->type->marshal('Y'));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->type->marshal(ArticleStatus::PUBLISHED));
        $this->expectException(InvalidArgumentException::class);
        $this->type->marshal(1);
    }

    /**
     * Check adding entity fields with an enum instance
     */
    public function testTableAddWithEnum(): void
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
     * Check adding entity fields with scalar value representing enum
     */
    public function testTableAddWithScalarValue(): void
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
     * Check adding entity fields with invalid scalar value sets error on field
     */
    public function testTableAddWithInvalidScalarValue(): void
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
     * Check to get an entity and automatically transform field to an enum instance
     */
    public function testTableGet(): void
    {
        $articles = $this->getArticlesTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->get(1);
        $this->assertSame(ArticleStatus::PUBLISHED, $entity->published);
    }

    /**
     * Check updating an entity via an enum instance
     */
    public function testTableUpdateWithEnum(): void
    {
        $articles = $this->getArticlesTable();
        /** @var \TestApp\Model\Entity\Article $entity */
        $entity = $articles->get(1);
        $entity->published = ArticleStatus::UNPUBLISHED;
        $articles->save($entity);
        $this->assertSame(ArticleStatus::UNPUBLISHED, $entity->published);
    }

    private function getArticlesTable(): Table
    {
        $articles = $this->getTableLocator()->get('Articles');
        $articles->getSchema()->setColumnType('published', EnumType::for(ArticleStatus::class));

        return $articles;
    }
}
