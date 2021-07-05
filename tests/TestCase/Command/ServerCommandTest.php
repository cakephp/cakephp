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
 * @since         3.1.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Command;

use Cake\Command\ServerCommand;
use Cake\TestSuite\TestCase;

/**
 * ServerShell test.
 */
class ServerCommandTest extends TestCase
{
    /**
     * @var \Cake\Command\ServerCommand
     */
    protected $command;

    /**
     * setup method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->command = new ServerCommand();
    }

    /**
     * Test that the option parser is shaped right.
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->command->getOptionParser();
        $options = $parser->options();
        $this->assertArrayHasKey('host', $options);
        $this->assertArrayHasKey('port', $options);
        $this->assertArrayHasKey('ini_path', $options);
        $this->assertArrayHasKey('document_root', $options);
    }
}
