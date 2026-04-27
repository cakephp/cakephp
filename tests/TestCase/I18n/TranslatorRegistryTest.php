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
use InvalidArgumentException;
use TestApp\Cache\Engine\RecordingCacheEngine;
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

    /**
     * Default cache key format is unchanged when no prefix is set.
     */
    public function testDefaultCacheKeyFormatUnchanged(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $registry->get('default');

        $this->assertSame(['translations.default.en_CA'], $cacher->reads);
        $this->assertSame(['translations.default.en_CA'], array_keys($cacher->store));
    }

    /**
     * A static string prefix is injected into cache keys.
     */
    public function testStringPrefixInCacheKey(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $registry->setCacheKeyPrefix('tenant_a');
        $registry->get('default');

        $this->assertSame(['translations.tenant_a.default.en_CA'], $cacher->reads);
    }

    /**
     * A Closure prefix is evaluated on every get() call so the current
     * tenant identifier is always pulled fresh from user-land.
     */
    public function testClosurePrefixEvaluatedPerGet(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $state = new class {
            public string $current = 'tenant_a';

            public int $calls = 0;
        };
        $registry->setCacheKeyPrefix(function () use ($state): string {
            $state->calls++;

            return $state->current;
        });

        $registry->get('default');
        $state->current = 'tenant_b';
        $registry->get('default');

        $this->assertSame(2, $state->calls);
        $this->assertSame(
            ['translations.tenant_a.default.en_CA', 'translations.tenant_b.default.en_CA'],
            $cacher->reads,
        );
    }

    /**
     * Two different prefixes must not cross-contaminate either the
     * persistent cache or the in-memory registry bucket.
     */
    public function testPrefixesIsolateInMemoryAndPersistentLookups(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $state = new class {
            public string $current = 'tenant_a';
        };
        $registry->setCacheKeyPrefix(function () use ($state): string {
            return $state->current;
        });

        $translatorA = $registry->get('default');
        $state->current = 'tenant_b';
        $translatorB = $registry->get('default');

        $this->assertNotSame($translatorA, $translatorB, 'Different prefixes must resolve distinct translators.');

        $state->current = 'tenant_a';
        $this->assertSame($translatorA, $registry->get('default'), 'Same prefix must return the cached translator.');

        $this->assertSame(
            ['translations.tenant_a.default.en_CA', 'translations.tenant_b.default.en_CA'],
            array_keys($cacher->store),
            'Cache storage must keep one entry per prefix.',
        );
    }

    /**
     * Empty string prefix disables prefixing and restores the legacy format.
     */
    public function testEmptyPrefixRestoresLegacyKey(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $registry->setCacheKeyPrefix('tenant_a');
        $registry->get('default');
        $registry->setCacheKeyPrefix('');
        $registry->get('default');

        $this->assertSame(
            ['translations.tenant_a.default.en_CA', 'translations.default.en_CA'],
            $cacher->reads,
        );
    }

    /**
     * A Closure that returns an empty string also disables prefixing.
     */
    public function testClosurePrefixReturningEmptyStringDisablesPrefixing(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');

        $registry->setCacheKeyPrefix(fn(): string => '');
        $registry->get('default');

        $this->assertSame(['translations.default.en_CA'], $cacher->reads);
    }

    /**
     * Invalid characters in a static prefix throw immediately.
     */
    public function testInvalidStringPrefixThrows(): void
    {
        [$registry] = $this->buildRegistryWithRecordingCacher('en_CA');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tenant/one');
        $registry->setCacheKeyPrefix('tenant/one');
    }

    /**
     * Invalid characters returned by a Closure prefix throw at resolution time.
     */
    public function testInvalidClosurePrefixThrowsOnGet(): void
    {
        [$registry] = $this->buildRegistryWithRecordingCacher('en_CA');

        $registry->setCacheKeyPrefix(fn(): string => 'bad key');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bad key');
        $registry->get('default');
    }

    /**
     * clear() drops cached translator instances without clearing the cacher
     * or resetting configuration.
     */
    public function testClearDropsEntriesOnly(): void
    {
        [$registry, $cacher] = $this->buildRegistryWithRecordingCacher('en_CA');
        $registry->setCacheKeyPrefix('tenant_a');

        $registry->get('default');
        $cacher->store = [];
        $cacher->reads = [];

        $registry->clear();
        $registry->get('default');

        $this->assertSame(['translations.tenant_a.default.en_CA'], $cacher->reads);
    }

    /**
     * @param string $locale Default locale for the registry.
     * @return array{\Cake\I18n\TranslatorRegistry, \TestApp\Cache\Engine\RecordingCacheEngine}
     */
    protected function buildRegistryWithRecordingCacher(string $locale): array
    {
        $package = new Package('default');
        $packageLocator = new PackageLocator([
            'default' => [
                $locale => $package,
            ],
        ]);
        $formatterLocator = new FormatterLocator([
            'default' => SprintfFormatter::class,
        ]);

        $cacher = new RecordingCacheEngine();

        $registry = new TranslatorRegistry($packageLocator, $formatterLocator, $locale);
        $registry->setCacher($cacher);

        return [$registry, $cacher];
    }
}
