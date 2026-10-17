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

use Cake\I18n\Extractor\PhpStringLiteral;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Checks literal decoding against PHP string escape semantics without evaluation.
 */
class PhpStringLiteralTest extends TestCase
{
    /**
     * Decode source literals before the command escapes catalog output.
     *
     * @param string $source Quoted PHP literal token.
     * @param string $expected Decoded text.
     */
    #[DataProvider('literalProvider')]
    public function testDecode(string $source, string $expected): void
    {
        $this->assertSame($expected, PhpStringLiteral::decode($source));
    }

    /**
     * Supply literal, binary-prefixed, and escaped string forms supported by PHP.
     *
     * @return array<string, array{string, string}>
     */
    public static function literalProvider(): array
    {
        return [
            'single quote' => ["'It\\'s ready'", "It's ready"],
            'single quote keeps escapes' => ["'Line\\nNext'", 'Line\nNext'],
            'double quote newline' => ['"Line\nNext"', "Line\nNext"],
            'unknown escape' => ['"Path\z"', 'Path\z'],
            'unicode' => ['"\u{1F680}"', '🚀'],
            'octal' => ['"\101"', 'A'],
            'hexadecimal' => ['"\x41"', 'A'],
            'dollar and quote' => ['"\$name \"quoted\""', '$name "quoted"'],
            'binary single quote' => ["b'Title'", 'Title'],
            'binary double quote' => ['B"Line\nNext"', "Line\nNext"],
            'backslash' => ['"\\\\n"', '\n'],
        ];
    }
}
