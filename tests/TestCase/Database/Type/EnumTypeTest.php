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
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use PDO;
use TestApp\Model\Enum\ArticleStatus;

/**
 * Test for the String type.
 */
class EnumTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\TypeInterface
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = TypeFactory::build('enum');
        $this->driver = $this->getMockBuilder(Driver::class)->getMock();
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
        $this->assertSame(1, $this->type->toPHP(1, $this->driver));
        $this->assertSame('Y', $this->type->toPHP('Y', $this->driver));
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
        $this->assertSame(1, $this->type->marshal(1));
        $this->assertSame('Y', $this->type->marshal('Y'));
        $this->assertSame(ArticleStatus::PUBLISHED, $this->type->marshal(ArticleStatus::PUBLISHED));
    }
}
