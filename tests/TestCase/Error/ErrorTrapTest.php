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
 * @link          https://cakephp.org CakePHP Project
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Error;

use Cake\Error\ErrorLogger;
use Cake\Error\ErrorTrap;
use Cake\Error\Renderer\HtmlRenderer;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use stdClass;

class ErrorTrapTest extends TestCase
{
    public function testSetErrorRendererInvalid()
    {
        $trap = new ErrorTrap();
        $this->expectException(InvalidArgumentException::class);
        $trap->setErrorRenderer(stdClass::class);
    }

    public function testSetErrorRenderer()
    {
        $trap = new ErrorTrap();
        $trap->setErrorRenderer(HtmlRenderer::class);
        $this->assertInstanceOf(HtmlRenderer::class, $trap->renderer());
    }

    public function testSetLoggerInvalid()
    {
        $trap = new ErrorTrap();
        $this->expectException(InvalidArgumentException::class);
        $trap->setLogger(stdClass::class);
    }

    public function testSetLogger()
    {
        $trap = new ErrorTrap();
        $trap->setLogger(ErrorLogger::class);
        $this->assertInstanceOf(ErrorLogger::class, $trap->logger());
    }
}
