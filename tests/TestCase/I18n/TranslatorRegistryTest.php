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
 * @since         3.4.6
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\I18n;

use Cake\I18n\Formatter\SprintfFormatter;
use Cake\I18n\FormatterLocator;
use Cake\I18n\Package;
use Cake\I18n\PackageLocator;
use Cake\I18n\Translator;
use Cake\I18n\TranslatorRegistry;
use Cake\TestSuite\TestCase;

class TranslatorRegistryTest extends TestCase
{
    /**
     * Test Package null initialization from cache
     */
    public function testGetNullPackageInitializationFromCache(): void
    {
        $packageLocator = $this->getMockBuilder(PackageLocator::class)->getMock();
        $package = $this->getMockBuilder(Package::class)->getMock();
        $formatter = $this->getMockBuilder(SprintfFormatter::class)->getMock();
        $formatterLocator = $this->getMockBuilder(FormatterLocator::class)->getMock();
        $cacheEngineNullPackage = $this->getMockForAbstractClass('Cake\Cache\CacheEngine', [], '', true, true, true, ['read']);
        $translatorNullPackage = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();

        $translatorNonNullPackage = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $translatorNonNullPackage
            ->method('getPackage')
            ->willReturn($package);

        $formatterLocator
            ->method('get')
            ->willReturn($formatter);

        $package
            ->method('getFormatter')
            ->willReturn('basic');

        $packageLocator->method('get')
            ->willReturn($package);

        $cacheEngineNullPackage
            ->method('read')
            ->willReturn($translatorNullPackage);

        $registry = new TranslatorRegistry($packageLocator, $formatterLocator, 'en_CA');
        $registry->setCacher($cacheEngineNullPackage);

        $this->assertNotNull($registry->get('default')->getPackage());
    }
}
