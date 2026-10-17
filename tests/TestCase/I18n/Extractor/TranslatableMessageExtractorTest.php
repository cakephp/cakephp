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
use Cake\I18n\Extractor\TranslatableMessageExtractor;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks constructor discovery without loading or evaluating application PHP source.
 */
class TranslatableMessageExtractorTest extends TestCase
{
    /**
     * Resolve valid class names and literal metadata across PHP declaration styles.
     *
     * @param string $code PHP source containing a constructor.
     */
    #[DataProvider('sourceProvider')]
    public function testExtractsLiteralMetadata(string $code): void
    {
        $extractor = new TranslatableMessageExtractor();
        $this->assertEquals([
            new ExtractedMessage('Article title', 'forms', 'heading', line: 1),
        ], iterator_to_array($extractor->extract('example.php', '<?php ' . $code)));
    }

    /**
     * Supply imported and fully qualified constructors in runtime code and attributes.
     *
     * @return array<string, array{string}>
     */
    public static function sourceProvider(): array
    {
        $call = "('Article title', ['name' => service(1, 2)], 'forms', 'heading')";

        return [
            'fully qualified' => ['new \\Cake\\I18n\\TranslatableMessage' . $call . ';'],
            'import' => ['use Cake\\I18n\\TranslatableMessage; new TranslatableMessage' . $call . ';'],
            'alias' => ['namespace App; use Cake\\I18n\\TranslatableMessage as Message; new Message' . $call . ';'],
            'namespace alias' => ['use Cake\\I18n as Intl; new Intl\\TranslatableMessage' . $call . ';'],
            'group import' => ['use Cake\\I18n\\{I18n, TranslatableMessage as Message}; new Message' . $call . ';'],
            'multiple imports' => ['use Cake\\I18n\\I18n, Cake\\I18n\\TranslatableMessage; new TranslatableMessage' . $call . ';'],
            'same namespace' => ['namespace Cake\\I18n; new TranslatableMessage' . $call . ';'],
            'relative namespace' => ['namespace Cake; new namespace\\I18n\\TranslatableMessage' . $call . ';'],
            'bracketed namespace' => ['namespace App { use Cake\\I18n\\TranslatableMessage as Message; new Message' . $call . '; }'],
            'attribute' => ['use Cake\\I18n\\TranslatableMessage as Message; #[Field(label: new Message' . $call . ')] class Article {}'],
            'named arguments' => ["use Cake\\I18n\\TranslatableMessage as Message; new Message(context: 'heading', domain: 'forms', message: 'Article title');"],
            'concatenation' => ["use Cake\\I18n\\TranslatableMessage as Message; new Message('Article' . ' title', domain: 'forms', context: 'heading');"],
            'nested formatting values' => ["use Cake\\I18n\\TranslatableMessage as Message; new Message('Article title', ['callback' => function () { return [1, 2]; }], 'forms', 'heading');"],
        ];
    }

    /**
     * Defaults remain stable while formatting argument expressions need no evaluation.
     */
    public function testDefaultsCommentsAndNestedMessages(): void
    {
        $source = <<<'PHP'
<?php
use Cake\I18n\TranslatableMessage as Message;
$factory = static function () use ($runtime) {
    return new Message(/* source */ 'First', arguments: [$runtime]);
};
new Message('Second', [new Message('Nested')]);
PHP;
        $extractor = new TranslatableMessageExtractor();
        $messages = iterator_to_array($extractor->extract('example.php', $source));
        $this->assertSame(['First', 'Second', 'Nested'], array_column($messages, 'message'));
        $this->assertSame(['default', 'default', 'default'], array_column($messages, 'domain'));
        $this->assertSame([4, 6, 6], array_column($messages, 'line'));
    }

    /**
     * Dynamic metadata is reported as unresolved instead of guessed into the wrong catalog.
     */
    public function testDynamicMetadataRemainsUnresolved(): void
    {
        $extractor = new TranslatableMessageExtractor();
        $messages = iterator_to_array($extractor->extract('example.php', <<<'PHP'
<?php
use Cake\I18n\TranslatableMessage;
new TranslatableMessage($message);
new TranslatableMessage('Title', domain: $domain);
new TranslatableMessage('Title', context: Context::HEADING);
new TranslatableMessage("Title {$name}");
new TranslatableMessage('Title', ...$configuration);
PHP));
        $this->assertCount(5, $messages);
        foreach ($messages as $diagnostic) {
            $this->assertInstanceOf(ExtractionDiagnostic::class, $diagnostic);
            $this->assertStringContainsString('use literal message, domain, and context strings.', $diagnostic->message);
            $this->assertStringContainsString('example.php on line', $diagnostic->message);
        }
    }

    /**
     * Unrelated classes, imports, comments, strings, and anonymous classes must not produce messages.
     */
    public function testUnrelatedConstructorsAndNamespaceImportsAreIgnored(): void
    {
        $extractor = new TranslatableMessageExtractor();
        $this->assertSame([], iterator_to_array($extractor->extract('example.php', <<<'PHP'
<?php
namespace App {
    use Other\TranslatableMessage;
    new TranslatableMessage('Wrong class');
    new class('Anonymous') {};
    $source = "new TranslatableMessage('String content')";
}
namespace Elsewhere {
    use function Cake\I18n\TranslatableMessage;
    new TranslatableMessage('Function import is not a class import');
}
namespace Third;
new TranslatableMessage('Unimported class');
PHP)));
    }
}
