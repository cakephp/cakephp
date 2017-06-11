<?php
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

use Aura\Intl\Package;
use Aura\Intl\Translator as AuraTranslator;
use Cake\I18n\TranslatorFactory;
use Cake\TestSuite\TestCase;

/**
 * TranslatorFactory Test class
 */
class TranslatorFactoryTest extends TestCase
{
    /**
     * Test that errors are emitted when stale cache files are found.
     *
     * @expectedException RuntimeException
     * @expectedExceptionMessage Translator fallback class
     */
    public function testNewInstanceErrorOnFallback()
    {
        $formatter = $this->getMockBuilder('Aura\Intl\FormatterInterface')->getMock();
        $package = $this->getMockBuilder(Package::class)->getMock();
        $fallback = new AuraTranslator('en_CA', $package, $formatter, null);
        $factory = new TranslatorFactory();
        $factory->newInstance('en_CA', $package, $formatter, $fallback);
    }
}
