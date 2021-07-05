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
 * @since         3.6.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Core\Exception\CakeException;
use Cake\Database\Type\BinaryUuidType;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use PDO;

/**
 * Test for the Binary uuid type.
 */
class BinaryUuidTypeTest extends TestCase
{
    /**
     * @var \Cake\Database\Type\BinaryUuidType
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
        $this->type = new BinaryUuidType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test toPHP
     */
    public function testToPHP(): void
    {
        $this->assertNull($this->type->toPHP(null, $this->driver));

        $result = $this->type->toPHP(Text::uuid(), $this->driver);
        $uuidRegex = '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/';
        preg_match_all(
            $uuidRegex,
            $result,
            $matches
        );

        $result = $matches[0];
        $this->assertSame(count($result), 2);

        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toPHP($fh, $this->driver);
        $this->assertSame($fh, $result);
        $this->assertIsResource($result);
        fclose($fh);
    }

    /**
     * Test exceptions on invalid data.
     */
    public function testToPHPFailure(): void
    {
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to convert array into binary uuid.');

        $this->type->toPHP([], $this->driver);
    }

    /**
     * Test converting to database format
     */
    public function testToDatabase(): void
    {
        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toDatabase($fh, $this->driver);
        $this->assertSame($fh, $result);

        $value = Text::uuid();
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame(str_replace('-', '', $value), unpack('H*', $result)[1]);
    }

    /**
     * Test converting to database format fails
     */
    public function testToDatabaseInvalid(): void
    {
        $value = 'mUMPWUxCpaCi685A9fEwJZ';
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertNull($result);
    }

    /**
     * Test that the PDO binding type is correct.
     */
    public function testToStatement(): void
    {
        $this->assertSame(PDO::PARAM_LOB, $this->type->toStatement('', $this->driver));
    }
}
