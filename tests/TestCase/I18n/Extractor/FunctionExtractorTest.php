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

use Cake\I18n\Extractor\ExtractedMessage;
use Cake\I18n\Extractor\ExtractionDiagnostic;
use Cake\I18n\Extractor\FunctionExtractor;
use Cake\TestSuite\TestCase;

/**
 * Verifies the function extractor's common metadata and diagnostic contract.
 */
class FunctionExtractorTest extends TestCase
{
    /**
     * Each translation function maps its domain, context, and plural arguments correctly.
     */
    public function testExtractsAllTranslationFunctionSignatures(): void
    {
        $source = <<<'PHP'
<?php
__('Title');
__n('Title', 'Titles', $count);
__d('forms', 'Title');
__dn('forms', 'Title', 'Titles', $count);
__x('heading', 'Title');
__xn('heading', 'Title', 'Titles', $count);
__dx('forms', 'heading', 'Title');
__dxn('forms', 'heading', 'Title', 'Titles', $count);
PHP;
        $messages = iterator_to_array(new FunctionExtractor()->extract('example.php', $source));
        $this->assertEquals([
            new ExtractedMessage('Title', line: 2),
            new ExtractedMessage('Title', plural: 'Titles', line: 3),
            new ExtractedMessage('Title', 'forms', line: 4),
            new ExtractedMessage('Title', 'forms', plural: 'Titles', line: 5),
            new ExtractedMessage('Title', context: 'heading', line: 6),
            new ExtractedMessage('Title', context: 'heading', plural: 'Titles', line: 7),
            new ExtractedMessage('Title', 'forms', 'heading', line: 8),
            new ExtractedMessage('Title', 'forms', 'heading', 'Titles', line: 9),
        ], $messages);
    }

    /**
     * Source text remains unescaped, while methods and declarations are not function markers.
     */
    public function testCommentsConcatenationNumericLiteralsAndUnrelatedMethods(): void
    {
        $source = <<<'PHP'
<?php
\__(/* description */ 'First' . "\nSecond");
__(0);
$object->__('Method');
$object?->__('Nullsafe method');
Utility::__('Static method');
function __($text) {}
PHP;
        $messages = iterator_to_array(new FunctionExtractor()->extract('example.php', $source));
        $this->assertEquals([
            new ExtractedMessage("First\nSecond", line: 2),
            new ExtractedMessage('0', line: 3),
        ], $messages);
    }

    /**
     * Dynamic or incomplete function metadata yields diagnostics rather than partial messages.
     */
    public function testInvalidMarkersProvideTheSourceLocationAndCall(): void
    {
        $results = iterator_to_array(new FunctionExtractor()->extract('example.php', <<<'PHP'
<?php
__($message);
__d($domain, 'Title');
__n('Title');
__('Title' . $suffix);
PHP));
        $this->assertCount(4, $results);
        foreach ($results as $result) {
            $this->assertInstanceOf(ExtractionDiagnostic::class, $result);
            $this->assertTrue($result->invalidMarker);
            $this->assertStringContainsString('Invalid marker content in example.php:', $result->message);
        }
        $this->assertStringContainsString('* __($message)', $results[0]->message);
    }
}
