<?php
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
    public $type;

    /**
     * @var \Cake\Database\Driver
     */
    public $driver;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->type = new BinaryUuidType();
        $this->driver = $this->getMockBuilder('Cake\Database\Driver')->getMock();
    }

    /**
     * Test toPHP
     *
     * @return void
     */
    public function testToPHP()
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
        $this->assertInternalType('resource', $result);
        fclose($fh);
    }

    /**
     * Test exceptions on invalid data.
     *
     * @expectedException \Cake\Core\Exception\Exception
     * @expectedExceptionMessage Unable to convert array into binary uuid.
     */
    public function testToPHPFailure()
    {
        $this->type->toPHP([], $this->driver);
    }

    /**
     * Test converting to database format
     *
     * @return void
     */
    public function testToDatabase()
    {
        $fh = fopen(__FILE__, 'r');
        $result = $this->type->toDatabase($fh, $this->driver);
        $this->assertSame($fh, $result);

        $value = Text::uuid();
        $result = $this->type->toDatabase($value, $this->driver);
        $this->assertSame(str_replace('-', '', $value), unpack('H*', $result)[1]);
    }

    /**
     * Test that the PDO binding type is correct.
     *
     * @return void
     */
    public function testToStatement()
    {
        $this->assertEquals(PDO::PARAM_LOB, $this->type->toStatement('', $this->driver));
    }
}
