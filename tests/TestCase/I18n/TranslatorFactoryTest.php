<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.3.14
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\I18n\FormatterInterface;
use Cake\I18n\Package;
use Cake\I18n\Translator;
use Cake\I18n\TranslatorFactory;
use Cake\TestSuite\TestCase;

/**
 * TranslatorFactory Test class
 */
class TranslatorFactoryTest extends TestCase
{
    /**
     * Test that errors are emitted when stale cache files are found.
     */
    public function testNewInstanceErrorOnFallback()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Translator fallback class');
        $formatter = $this->getMockBuilder(FormatterInterface::class)->getMock();
        $package = $this->getMockBuilder(Package::class)->getMock();
        $fallback = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $factory = new TranslatorFactory();
        $factory->newInstance('en_CA', $package, $formatter, $fallback);
    }
}
