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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Database\Type;

use Cake\Cache\Cache;
use Cake\I18n\I18n;
use Cake\I18n\Package;
use Cake\TestSuite\TestCase;
use TestApp\Model\Enum\ArticleDomainStatus;
use TestApp\Model\Enum\ArticleStatusTrait;
use TestApp\Model\Enum\ArticleStatusTraitLabeled;

/**
 * Tests for EnumLabelTrait.
 */
class EnumLabelTraitTest extends TestCase
{
    /**
     * Restore i18n state after each test so translator changes do not bleed between tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        Cache::clear('_cake_translations_');
        I18n::clear();
        I18n::setLocale(I18n::getDefaultLocale());
    }

    /**
     * Test that label() returns a humanized version of the case name when no Label attribute is present.
     */
    public function testLabelWithoutAttribute(): void
    {
        $this->assertSame('Published', ArticleStatusTrait::Published->label());
        $this->assertSame('Unpublished', ArticleStatusTrait::Unpublished->label());
        $this->assertSame('Pending Review', ArticleStatusTrait::PendingReview->label());
    }

    /**
     * Test that label() returns the value from the Label attribute when present.
     */
    public function testLabelWithAttribute(): void
    {
        $this->assertSame('Article is published', ArticleStatusTraitLabeled::Published->label());
        $this->assertSame('Article is not published', ArticleStatusTraitLabeled::Unpublished->label());
    }

    /**
     * Test that label() falls back to a humanized name when the Label attribute is absent,
     * even in an enum that has the attribute on other cases.
     */
    public function testLabelFallbackWithoutAttributeInMixedEnum(): void
    {
        $this->assertSame('Pending Review', ArticleStatusTraitLabeled::PendingReview->label());
    }

    /**
     * Test that label() returns a consistent (cached) result on repeated calls.
     */
    public function testLabelIsCached(): void
    {
        $firstCall = ArticleStatusTrait::Published->label();
        $secondCall = ArticleStatusTrait::Published->label();

        $this->assertSame($firstCall, $secondCall);
    }

    /**
     * Test that label() returns a consistent (cached) result on repeated calls when using the Label attribute.
     */
    public function testLabelWithAttributeIsCached(): void
    {
        $firstCall = ArticleStatusTraitLabeled::Published->label();
        $secondCall = ArticleStatusTraitLabeled::Published->label();

        $this->assertSame($firstCall, $secondCall);
    }

    /**
     * Test that two different enums sharing the same case name produce independent labels.
     */
    public function testDifferentEnumsWithSameValueHaveIndependentLabels(): void
    {
        // Both cases share the same name
        $this->assertSame(ArticleStatusTrait::Published->name, ArticleStatusTraitLabeled::Published->name);

        // warm up the local static variable cache for both enums
        $this->assertSame('Published', ArticleStatusTrait::Published->label());
        $this->assertSame('Article is published', ArticleStatusTraitLabeled::Published->label());

        // call again to get the cached value and show they still produce different labels
        $this->assertSame('Published', ArticleStatusTrait::Published->label());
        $this->assertSame('Article is published', ArticleStatusTraitLabeled::Published->label());
    }

    /**
     * Test that label() passes its result through the i18n __() function.
     *
     * Both the humanized fallback and explicit Label attribute values are translated,
     * allowing applications to provide translations for enum labels.
     *
     * A locale with no pre-loaded .po file (fr_FR) is used so the custom Package
     * is not shadowed by a cached translation file.
     */
    public function testLabelPassesThroughI18nTranslation(): void
    {
        Cache::clear('_cake_translations_');
        I18n::clear();

        I18n::setTranslator('default', function () {
            $package = new Package();
            $package->setMessages([
                'Published' => 'Publié',
                'Pending Review' => 'En attente de révision',
                'Article is published' => "L'article est publié",
            ]);

            return $package;
        }, 'fr_FR');
        I18n::setLocale('fr_FR');

        // Humanized fallback labels are translated.
        $this->assertSame('Publié', ArticleStatusTrait::Published->label());
        $this->assertSame('En attente de révision', ArticleStatusTrait::PendingReview->label());

        // Explicit Label attribute values are also translated.
        $this->assertSame("L'article est publié", ArticleStatusTraitLabeled::Published->label());
    }

    /**
     * Test that label() uses the context from the Label attribute when present.
     */
    public function testLabelWithAttributeContextUsesTranslationContext(): void
    {
        Cache::clear('_cake_translations_');
        I18n::clear();

        I18n::setTranslator('default', function () {
            $package = new Package();
            $package->setMessages([
                'Article is a draft' => [
                    '_context' => [
                        'ArticleStatus' => 'Article est un brouillon',
                    ],
                ],
            ]);

            return $package;
        }, 'fr_FR');
        I18n::setLocale('fr_FR');

        $this->assertSame('Article est un brouillon', ArticleStatusTraitLabeled::Draft->label());
    }

    /**
     * Test that label() uses the domain from the Label attribute when present.
     */
    public function testLabelWithAttributeUsesDomain(): void
    {
        Cache::clear('_cake_translations_');
        I18n::clear();

        I18n::setTranslator('news', function () {
            $package = new Package();
            $package->setMessages([
                'Article is published in the news domain' => [
                    '_context' => [
                        'Article' => 'Un article est publié dans la presse.',
                    ],
                ],
                'Article is unpublished in the news domain' => 'Un article est non publié dans la presse.',
            ]);

            return $package;
        }, 'fr_FR');
        I18n::setLocale('fr_FR');

        $this->assertSame('Un article est publié dans la presse.', ArticleDomainStatus::Published->label());
        $this->assertSame('Un article est non publié dans la presse.', ArticleDomainStatus::Unpublished->label());
    }
}
