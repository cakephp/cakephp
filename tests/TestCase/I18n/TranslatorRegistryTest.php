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
use TestApp\Cache\Engine\TestAppCacheEngine;

class TranslatorRegistryTest extends TestCase
{
    /**
     * Test Package null initialization from cache
     */
    public function testGetNullPackageInitializationFromCache(): void
    {
        $package = new Package('default');
        $packageLocator = new PackageLocator([
            'default' => [
                'en_CA' => $package,
            ],
        ]);
        $formatterLocator = new FormatterLocator([
            'default' => SprintfFormatter::class,
        ]);

        $cachedTranslator = new Translator('en_CA', $package, new SprintfFormatter());
        $cacheEngineNullPackage = new class ($cachedTranslator) extends TestAppCacheEngine {
            public function __construct(protected Translator $translator)
            {
            }

            public function get($key, $default = null): mixed
            {
                return $this->translator;
            }
        };

        $registry = new TranslatorRegistry($packageLocator, $formatterLocator, 'en_CA');
        $registry->setCacher($cacheEngineNullPackage);

        $this->assertSame($package, $registry->get('default')->getPackage());
    }
}
