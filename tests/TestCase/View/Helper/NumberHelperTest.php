<?php
declare(strict_types=1);

/**
 * NumberHelperTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         1.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\Helper\NumberHelper;
use Cake\View\View;

/**
 * NumberHelperTest class
 */
class NumberHelperTest extends TestCase
{
    /**
     * @var \Cake\View\View
     */
    protected $View;

    /**
     * setUp method
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View();

        static::setAppNamespace();
    }

    /**
     * tearDown method
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->clearPlugins();
        unset($this->View);
    }

    /**
     * Provider for method proxying.
     *
     * @return array
     */
    public static function methodProvider(): array
    {
        return [
            ['precision', 1.23],
            ['toReadableSize', 1.23],
            ['toPercentage', 1.23],
        ];
    }

    /**
     * Tests calls are proxied to Number class.
     *
     * @dataProvider methodProvider
     */
    public function testMethodProxying(string $method, mixed $arg): void
    {
        $helper = new NumberHelper($this->View);
        $this->assertNotNull($helper->{$method}($arg));
    }
}
