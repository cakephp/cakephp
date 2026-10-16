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
namespace Cake\Test\TestCase\I18n\Extractor;

use Cake\Database\Type\Attribute\Label;
use Cake\I18n\Extractor\EnumLabelExtractor;
use Cake\I18n\Extractor\ExtractedMessage;
use Cake\I18n\Extractor\ExtractionDiagnostic;
use Cake\TestSuite\TestCase;

/**
 * Verifies enum extraction through configured attributes without a Database dependency.
 */
class EnumLabelExtractorTest extends TestCase
{
    /**
     * Reflection passes label metadata to the configured converter, including context and defaults.
     */
    public function testConfiguredAttributeAndMessageConversion(): void
    {
        $extractor = new EnumLabelExtractor(
            Label::class,
            static fn(Label $label): ExtractedMessage => new ExtractedMessage(
                $label->label,
                'mapped_' . $label->domain,
                $label->context,
            ),
        );
        $file = TEST_APP . 'TestApp/Model/Enum/ArticleStatusExtract.php';
        $messages = iterator_to_array($extractor->extract($file, (string)file_get_contents($file)));
        $this->assertEquals([
            new ExtractedMessage('Published', 'mapped_default'),
            new ExtractedMessage('Unpublished', 'mapped_default'),
            new ExtractedMessage('Archived', 'mapped_default', 'article_status'),
        ], $messages);
    }

    /**
     * Missing classes produce a diagnostic for the command instead of console output.
     */
    public function testUnreflectableClassProducesDiagnostic(): void
    {
        $extractor = new EnumLabelExtractor(
            Label::class,
            static fn(Label $label): ExtractedMessage => new ExtractedMessage($label->label),
        );
        $results = iterator_to_array($extractor->extract(
            'missing.php',
            '<?php namespace MissingApplication; enum ArticleStatus {}',
        ));
        $this->assertCount(1, $results);
        $this->assertInstanceOf(ExtractionDiagnostic::class, $results[0]);
        $this->assertStringContainsString('MissingApplication\\ArticleStatus', $results[0]->message);
        $this->assertStringContainsString('missing.php', $results[0]->message);
        $this->assertFalse($results[0]->invalidMarker);
    }

    /**
     * Ordinary classes and source without declarations cannot produce enum labels.
     */
    public function testNonEnumsAreIgnored(): void
    {
        $extractor = new EnumLabelExtractor(
            Label::class,
            static fn(Label $label): ExtractedMessage => new ExtractedMessage($label->label),
        );
        $this->assertSame([], iterator_to_array($extractor->extract('template.php', '<?php __("Title");')));
        $this->assertSame([], iterator_to_array($extractor->extract(
            __FILE__,
            '<?php namespace Cake\\Test\\TestCase\\I18n\\Extractor; class EnumLabelExtractorTest {}',
        )));
    }
}
