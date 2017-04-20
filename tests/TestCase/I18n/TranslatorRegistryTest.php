<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.4.6
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\I18n;

use Aura\Intl\BasicFormatter;
use Aura\Intl\FormatterLocator;
use Aura\Intl\Package;
use Aura\Intl\PackageLocator;
use Aura\Intl\TranslatorLocator;
use Cake\I18n\Translator;
use Cake\I18n\TranslatorFactory;
use Cake\I18n\TranslatorRegistry;
use Cake\TestSuite\TestCase;

class TranslatorRegistryTest extends TestCase
{
    /**
     * Test Package null initialization from cache
     */
    public function testGetNullPackageInitializationFromCache()
    {
        $translatorFactory = $this->getMockBuilder(TranslatorFactory::class)->getMock();
        $translatorLocator = $this->getMockBuilder(TranslatorLocator::class)->disableOriginalConstructor()->getMock();
        $packageLocator = $this->getMockBuilder(PackageLocator::class)->getMock();
        $package = $this->getMockBuilder(Package::class)->getMock();
        $formatter = $this->getMockBuilder(BasicFormatter::class)->getMock();
        $formatterLocator = $this->getMockBuilder(FormatterLocator::class)->getMock();
        $cacheEngineNullPackage = $this->getMockForAbstractClass('Cake\Cache\CacheEngine', [], '', true, true, true, ['read']);
        $translatorNullPackage = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();

        $translatorNonNullPackage = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $translatorNonNullPackage
            ->method('getPackage')
            ->willReturn($package);

        $translatorFactory
            ->method('newInstance')
            ->willReturn($translatorNonNullPackage);

        $formatterLocator
            ->method('get')
            ->willReturn($formatter);

        $translatorLocator
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

        $registry = new TranslatorRegistry($packageLocator, $formatterLocator, $translatorFactory, 'en_CA');
        $registry->setCacher($cacheEngineNullPackage);

        $this->assertNotNull($registry->get('default')->getPackage());
    }
}
