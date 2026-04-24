<?php
declare(strict_types=1);

namespace Cake\Test\TestCase\I18n;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\TestSuite\TestCase;
use ReflectionProperty;

/**
 * I18nContextTest class
 */
class I18nContextTest extends TestCase
{
    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        I18n::clear();
        if (Cache::getConfig('_cake_core_')) {
            Cache::clear('_cake_core_');
        }
    }

    /**
     * Test that setting context works on I18n proxy and propagates to the registry.
     *
     * @return void
     */
    public function testSetContext(): void
    {
        I18n::setContext('tenant_123');
        $registry = I18n::translators();

        $property = new ReflectionProperty($registry, '_context');
        $property->setAccessible(true);
        $this->assertSame('tenant_123', $property->getValue($registry));
    }

    /**
     * Test cache key generation when context is set.
     *
     * @return void
     */
    public function testCacheKeyWithContext(): void
    {
        $engine = $this->getMockBuilder('Cake\Cache\Engine\NullEngine')
            ->onlyMethods(['get', 'set'])
            ->getMock();

        I18n::translators()->setCacher($engine);
        I18n::setContext('my_app_context');

        // The expected key pattern is: translations.{context}.{domain}.{locale}
        $expectedKey = 'translations.my_app_context.default.en_US';

        $engine->expects($this->once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn(null);

        I18n::getTranslator('default', 'en_US');
    }

    /**
     * Test that empty context results in legacy cache key format (backward compatibility).
     *
     * @return void
     */
    public function testCacheKeyWithoutContext(): void
    {
        $engine = $this->getMockBuilder('Cake\Cache\Engine\NullEngine')
            ->onlyMethods(['get', 'set'])
            ->getMock();

        I18n::translators()->setCacher($engine);
        I18n::setContext(''); // No context

        $expectedKey = 'translations.default.en_US';

        $engine->expects($this->once())
            ->method('get')
            ->with($expectedKey)
            ->willReturn(null);

        I18n::getTranslator('default', 'en_US');
    }
}