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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\I18n;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\I18n\TranslatableMessage;
use Cake\TestSuite\TestCase;
use RuntimeException;

/**
 * Tests deferred message translation and locale-independent configuration reuse.
 */
class TranslatableMessageTest extends TestCase
{
    /**
     * Configure lightweight real translators for independent locale and domain tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        I18n::clear();
        Cache::clear('_cake_translations_');
        I18n::setDefaultFormatter('default');
        I18n::setLocale('en_US');
        I18n::setTranslator('default', static fn(): Package => new Package(messages: [
            'Title for {name}' => 'Titre pour {name}',
        ]), 'fr_FR');
        I18n::setTranslator('forms', static fn(): Package => new Package(messages: [
            'Title {0}' => ['_context' => ['heading' => 'Titre {0}', '' => 'Autre {0}']],
        ]), 'fr_FR');
    }

    /**
     * Restore translation state for the rest of the I18n suite.
     */
    protected function tearDown(): void
    {
        I18n::setLocale(I18n::getDefaultLocale());
        I18n::clear();
        Cache::clear('_cake_translations_');
        parent::tearDown();
    }

    /**
     * A message can be constructed before the request locale is selected and reused later.
     */
    public function testTranslationUsesTheCurrentLocaleWithoutCachingResults(): void
    {
        $message = new TranslatableMessage('Title for {name}', ['name' => '<Ada>']);
        $this->assertSame('Title for <Ada>', $message->translate());
        I18n::setLocale('fr_FR');
        $this->assertSame('Titre pour <Ada>', $message->translate());
        I18n::setLocale('en_US');
        $this->assertSame('Title for <Ada>', $message->translate());
        $this->assertSame('Title for {name}', $message->message);
    }

    /**
     * Explicit locales select a translator without changing the request's current locale.
     */
    public function testExplicitLocaleDomainContextAndPositionalArguments(): void
    {
        $message = new TranslatableMessage('Title {0}', ['<article>'], domain: 'forms', context: 'heading');
        $this->assertSame('Titre <article>', $message->translate('fr_FR'));
        $this->assertSame('en_US', I18n::getLocale());
        $this->assertSame('Title <article>', $message->translate());
        $this->assertSame('Autre title', new TranslatableMessage('Title {0}', ['title'], 'forms')->translate('fr_FR'));
    }

    /**
     * Missing messages use source text, and empty strings remain distinct from zero.
     */
    public function testMissingEmptyAndZeroMessages(): void
    {
        $this->assertSame('Missing <text>', new TranslatableMessage('Missing <text>')->translate());
        $this->assertSame('', new TranslatableMessage('')->translate());
        $this->assertSame('0', new TranslatableMessage('0')->translate());
    }

    /**
     * Message arguments retain CakePHP's ICU formatting, including plural expressions.
     */
    public function testIcuPluralFormatting(): void
    {
        $this->assertSame('1 file', new TranslatableMessage(
            '{count, plural, one{# file} other{# files}}',
            ['count' => 1],
        )->translate());
        $this->assertSame('2 files', new TranslatableMessage(
            '{count, plural, one{# file} other{# files}}',
            ['count' => 2],
        )->translate());
    }

    /**
     * Cached configuration stores message metadata rather than translated output or translator services.
     */
    public function testSerializedMetadataCanBeTranslatedInAnotherLocale(): void
    {
        $message = new TranslatableMessage('Title for {name}', ['name' => 'Ada']);
        $this->assertSame('Title for Ada', $message->translate());
        $restored = unserialize(serialize($message));
        $this->assertInstanceOf(TranslatableMessage::class, $restored);
        I18n::setLocale('fr_FR');
        $this->assertSame('Titre pour Ada', $restored->translate());
    }

    /**
     * Construction does not load translations, and translator failures propagate when used.
     */
    public function testTranslatorFailuresAreDeferredUntilTranslation(): void
    {
        I18n::setTranslator('broken', static fn(): never => throw new RuntimeException('Translator unavailable.'), 'fr_FR');
        $message = new TranslatableMessage('Title', domain: 'broken');
        $this->assertSame('Title', $message->message);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Translator unavailable.');
        try {
            $message->translate('fr_FR');
        } finally {
            $this->assertSame('en_US', I18n::getLocale());
        }
    }
}
