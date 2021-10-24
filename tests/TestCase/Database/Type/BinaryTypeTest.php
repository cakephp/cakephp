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
namespace Cake\Test\TestCase\Database\Type;

use Cake\Core\Exception\CakeException;
use Cake\Database\TypeFactory;
use Cake\TestSuite\TestCase;
use PDO;

/**
 * Test for the Binary type.
 */
class BinaryTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\BinaryType
     */
    protected $type;

    /**
     * @var \Cake\Database\Driver
     */
    protected $driver;

    /**
     * Setup
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->type = TypeFactory::build('binary');
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP('some data', $this->driver);
        $this->assertIsResource($result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toPHP($fh, $this->driver);
        $this->assertSame($fh, $result);
        fclose($fh);
    }

    /**
     * Test exceptions on invalid data.
     */
    public function testToPHPFailure(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to convert array into binary.');
        $this->type->toPHP([], $this->driver);
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $value = 'some data';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame($value, $result);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toDatabase($fh, $this->driver);
        $this->assertSame($fh, $result);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_LOB, $this->type->toStatement('', $this->driver));
    }
}
