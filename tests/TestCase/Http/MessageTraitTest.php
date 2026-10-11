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
 * Parts of this file are derived from laminas-diactoros
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @copyright     Copyright (c) 2020 Laminas Project a Series of LF Projects, LLC. (https://getlaminas.org/)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @license       https://github.com/laminas/laminas-diactoros/blob/3.9.x/LICENSE.md New BSD License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\Client\Request;
use Cake\Http\MessageTrait;
use Cake\Http\Stream;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Tests for MessageTrait
 */
class MessageTraitTest extends TestCase
{
    protected MessageInterface $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->message = $this->createMessage(new Stream('php://memory', 'rw'));
    }

    /**
     * Create a minimal message using the trait.
     */
    protected function createMessage(StreamInterface $stream): MessageInterface
    {
        return new class ($stream) implements MessageInterface {
            use MessageTrait;

            public function __construct(StreamInterface $stream)
            {
                $this->stream = $stream;
            }
        };
    }

    public function testProtocolHasAcceptableDefault(): void
    {
        $this->assertSame('1.1', $this->message->getProtocolVersion());
    }

    public function testProtocolMutatorReturnsCloneWithChanges(): void
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertSame('1.0', $message->getProtocolVersion());
        $this->assertSame('1.1', $this->message->getProtocolVersion());
    }

    public static function invalidProtocolVersionProvider(): array
    {
        return [
            '1-without-minor' => ['1'],
            '1-with-invalid-minor' => ['1.2'],
            '1-with-hotfix' => ['1.1.1'],
            '3-with-invalid-minor' => ['3.1'],
            '3-with-hotfix' => ['3.0.1'],
            '4-without-minor' => ['4'],
            '4-with-0-minor' => ['4.0'],
            'empty' => [''],
            'trailing-newline' => ["1.1\n"],
        ];
    }

    #[DataProvider('invalidProtocolVersionProvider')]
    public function testWithProtocolVersionRaisesExceptionForInvalidVersion(string $version): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withProtocolVersion($version);
    }

    public static function validProtocolVersionProvider(): array
    {
        return [
            '1.0' => ['1.0'],
            '1.1' => ['1.1'],
            '2' => ['2'],
            '2.0' => ['2.0'],
            '3' => ['3'],
            '3.0' => ['3.0'],
        ];
    }

    #[DataProvider('validProtocolVersionProvider')]
    public function testWithProtocolVersionDoesntRaiseExceptionForValidVersion(string $version): void
    {
        $message = $this->message->withProtocolVersion($version);
        $this->assertSame($version, $message->getProtocolVersion());
    }

    public function testUsesStreamProvidedInConstructorAsBody(): void
    {
        $stream = new Stream('php://memory', 'rw');
        $message = $this->createMessage($stream);
        $this->assertSame($stream, $message->getBody());
    }

    public function testBodyMutatorReturnsCloneWithChanges(): void
    {
        $original = $this->message->getBody();
        $stream = new Stream('php://memory', 'rw');
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
        $this->assertSame($original, $this->message->getBody());
    }

    public function testGetHeaderReturnsHeaderValueAsArray(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Foo', 'Bar'], $message->getHeader('X-Foo'));
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testGetHeaderIsCaseInsensitive(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertSame(['Foo'], $message->getHeader('x-foo'));
        $this->assertSame(['Foo'], $message->getHeader('X-FOO'));
        $this->assertSame('Foo', $message->getHeaderLine('x-FoO'));
    }

    public function testGetHeaderLineReturnsHeaderValueAsCommaConcatenatedString(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    public function testGetHeadersKeepsHeaderCaseSensitivity(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    public function testGetHeadersReturnsCaseWithWhichHeaderFirstRegistered(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    public function testHasHeaderReturnsFalseIfHeaderIsNotPresent(): void
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testHasHeaderReturnsTrueIfHeaderIsPresent(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
        $this->assertTrue($message->hasHeader('x-foo'));
    }

    public function testAddHeaderAppendsToExistingHeader(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertSame('Foo,Bar', $message2->getHeaderLine('X-Foo'));
        $this->assertSame('Foo', $message->getHeaderLine('X-Foo'));
    }

    public function testAddHeaderAppendsArrayValues(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('X-Foo', ['Bar', 'Baz']);
        $this->assertSame(['Foo', 'Bar', 'Baz'], $message->getHeader('X-Foo'));
    }

    public function testAddHeaderCreatesHeaderIfNotPresent(): void
    {
        $message = $this->message->withAddedHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['X-Foo' => ['Foo']], $message->getHeaders());
    }

    public function testCanRemoveHeaders(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testHeaderRemovalIsCaseInsensitive(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));

        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
        $this->assertSame([], $message2->getHeaders());
    }

    public static function invalidGeneralHeaderValues(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'array' => [['foo' => ['bar']]],
            'object' => [(object)['foo' => 'bar']],
        ];
    }

    #[DataProvider('invalidGeneralHeaderValues')]
    public function testWithHeaderRaisesExceptionForInvalidNestedHeaderValue(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        $this->message->withHeader('X-Foo', [$value]);
    }

    public static function invalidHeaderValues(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'object' => [(object)['foo' => 'bar']],
            'empty-array' => [[]],
        ];
    }

    #[DataProvider('invalidHeaderValues')]
    public function testWithHeaderRaisesExceptionForInvalidValueType(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        $this->message->withHeader('X-Foo', $value);
    }

    public function testWithHeaderReplacesDifferentCapitalization(): void
    {
        $this->message = $this->message->withHeader('X-Foo', ['foo']);
        $new = $this->message->withHeader('X-foo', ['bar']);
        $this->assertSame(['bar'], $new->getHeader('x-foo'));
        $this->assertSame(['X-foo' => ['bar']], $new->getHeaders());
    }

    #[DataProvider('invalidGeneralHeaderValues')]
    public function testWithAddedHeaderRaisesExceptionForNonStringNonArrayValue(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a string');

        $this->message->withAddedHeader('X-Foo', $value);
    }

    #[DataProvider('invalidGeneralHeaderValues')]
    public function testWithAddedHeaderToExistingHeaderRaisesExceptionForInvalidValue(mixed $value): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a string');

        $message->withAddedHeader('X-Foo', $value);
    }

    public function testWithoutHeaderDoesNothingIfHeaderDoesNotExist(): void
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }

    public function testHeadersInitialization(): void
    {
        $message = new Request('', 'GET', ['X-Foo' => ['bar']]);
        $this->assertSame(['bar'], $message->getHeader('X-Foo'));
        $this->assertArrayHasKey('X-Foo', $message->getHeaders());
    }

    public function testGetHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist(): void
    {
        $this->assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }

    public function testGetHeaderLineReturnsEmptyStringWhenHeaderDoesNotExist(): void
    {
        $this->assertSame('', $this->message->getHeaderLine('X-Foo-Bar'));
    }

    public static function headersWithInjectionVectors(): array
    {
        return [
            'name-with-cr' => ["X-Foo\r-Bar", 'value'],
            'name-with-lf' => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf' => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf' => ["X-Foo\r\n\r\n-Bar", 'value'],
            'name-with-trailing-lf' => ["X-Foo-Bar\n", 'value'],
            'name-with-leading-lf' => ["\nX-Foo-Bar", 'value'],
            'value-with-cr' => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf' => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf' => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf' => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr' => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf' => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf' => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
            'value-with-trailing-lf' => ['X-Foo-Bar', "value\n"],
            'value-with-leading-lf' => ['X-Foo-Bar', "\nvalue"],
        ];
    }

    /**
     * @param string $name
     * @param array<string>|string $value
     */
    #[DataProvider('headersWithInjectionVectors')]
    public function testDoesNotAllowCRLFInjectionWhenCallingWithHeader(string $name, array|string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withHeader($name, $value);
    }

    /**
     * @param string $name
     * @param array<string>|string $value
     */
    #[DataProvider('headersWithInjectionVectors')]
    public function testDoesNotAllowCRLFInjectionWhenCallingWithAddedHeader(string $name, array|string $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withAddedHeader($name, $value);
    }

    /**
     * @param string $name
     * @param array<string>|string $value
     */
    #[DataProvider('headersWithInjectionVectors')]
    public function testDoesNotAllowCRLFInjectionWhenAddingToExistingHeader(string $name, array|string $value): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', 'existing');

        $this->expectException(InvalidArgumentException::class);

        $message->withAddedHeader($name, $value);
    }

    public function testWithHeaderAllowsHeaderContinuations(): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame('value, second value', $message->getHeaderLine('X-Foo-Bar'));
    }

    public function testWithAddedHeaderAllowsHeaderContinuations(): void
    {
        $message = $this->message->withAddedHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame('value, second value', $message->getHeaderLine('X-Foo-Bar'));
    }

    public static function headersWithWhitespace(): array
    {
        return [
            'no' => ['Baz'],
            'leading' => [' Baz'],
            'trailing' => ['Baz '],
            'both' => [' Baz '],
            'mixed' => [" \t Baz\t \t"],
        ];
    }

    #[DataProvider('headersWithWhitespace')]
    public function testWithHeaderTrimsWhitespace(string $value): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', $value);
        $this->assertSame(trim($value, "\t "), $message->getHeaderLine('X-Foo-Bar'));
    }

    public static function headersWithContinuation(): array
    {
        return [
            'space' => ["foo\r\n bar"],
            'tab' => ["foo\r\n\tbar"],
        ];
    }

    #[DataProvider('headersWithContinuation')]
    public function testWithHeaderNormalizesContinuationToNotContainNewlines(string $value): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', $value);
        // Newlines must no longer appear.
        $this->assertStringNotContainsString("\r", $message->getHeaderLine('X-Foo-Bar'));
        $this->assertStringNotContainsString("\n", $message->getHeaderLine('X-Foo-Bar'));
        // But there must be at least one space.
        $this->assertStringContainsString(' ', $message->getHeaderLine('X-Foo-Bar'));
    }

    public static function numericHeaderValuesProvider(): array
    {
        return [
            'integer' => [123],
            'float' => [12.3],
        ];
    }

    #[DataProvider('numericHeaderValuesProvider')]
    public function testWithHeaderShouldAllowIntegersAndFloats(int|float $value): void
    {
        $message = $this->message
            ->withHeader('X-Test-Array', [$value])
            ->withHeader('X-Test-Scalar', $value);

        $this->assertSame([
            'X-Test-Array' => [(string)$value],
            'X-Test-Scalar' => [(string)$value],
        ], $message->getHeaders());
    }

    public static function invalidHeaderValueTypes(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'object' => [(object)['header' => ['foo', 'bar']]],
        ];
    }

    public static function invalidArrayHeaderValues(): array
    {
        $values = self::invalidHeaderValueTypes();
        $values['array'] = [['INVALID']];

        return $values;
    }

    #[DataProvider('invalidArrayHeaderValues')]
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderValuesInArrays(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        $this->message->withHeader('X-Test-Array', [$value]);
    }

    #[DataProvider('invalidHeaderValueTypes')]
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderScalarValues(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        $this->message->withHeader('X-Test-Scalar', $value);
    }

    /**
     * Header value validation cases ported from Diactoros' HeaderSecurityTest.
     */
    public static function validateValues(): array
    {
        return [
            ["This is a\n test", false],
            ["This is a\r test", false],
            ["This is a\n\r test", false],
            ["This is a\r\n  test", true],
            ["This is a \r\ntest", false],
            ["This is a \r\n\n test", false],
            ["This is a\n\n test", false],
            ["This is a\r\r test", false],
            ["This is a \r\r\n test", false],
            ["This is a \r\n\r\ntest", false],
            ["This is a \r\n\n\r\n test", false],
            ["This is a \xFF test", false],
            ["This is a \x7F test", false],
            ["This is a \x00 test", false],
            ["This is a \x7E test", true],
            ["This is a \t test", true],
            ["This is a \x80 test", true],
            ['This is a test', true],
            ["This is a test\n", false],
        ];
    }

    #[DataProvider('validateValues')]
    public function testValidatesValuesPerRfc7230(string $value, bool $expected): void
    {
        try {
            $this->message->withHeader('X-Test', $value);
            $valid = true;
        } catch (InvalidArgumentException) {
            $valid = false;
        }
        $this->assertSame($expected, $valid);
    }

    public static function assertValues(): array
    {
        return [
            ["This is a\n test"],
            ["This is a\r test"],
            ["This is a\n\r test"],
            ["This is a \r\ntest"],
            ["This is a \r\n\n test"],
            ["This is a\n\n test"],
            ["This is a\r\r test"],
            ["This is a \r\r\n test"],
            ["This is a \r\n\r\ntest"],
            ["This is a \r\n\n\r\n test"],
            ["This is a test\n"],
        ];
    }

    #[DataProvider('assertValues')]
    public function testAssertValidRaisesExceptionForInvalidValue(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid header value');

        $this->message->withAddedHeader('X-Test', $value);
    }

    public static function assertNames(): array
    {
        return [
            ["test\n"],
            ["\ntest"],
            ["foo\r\n bar"],
            ["f\x00o"],
            ['foo bar'],
            [':foo'],
            ['foo:'],
            [''],
            ['foo(bar)'],
            ['foo/bar'],
            ["f\xC3\xB6o"],
        ];
    }

    #[DataProvider('assertNames')]
    public function testAssertValidNameRaisesExceptionForInvalidName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid header name');

        $this->message->withHeader($name, 'value');
    }

    #[DataProvider('assertNames')]
    public function testWithAddedHeaderRaisesExceptionForInvalidName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not a valid header name');

        $this->message->withAddedHeader($name, 'value');
    }

    public function testValidHeaderNamesWithSpecialCharacters(): void
    {
        $name = "X-Foo_Bar.Baz!#$%&'*+^`|~1";
        $message = $this->message->withHeader($name, 'value');
        $this->assertSame('value', $message->getHeaderLine($name));
    }

    public function testClientRequestUsesTrait(): void
    {
        $request = new Request('http://example.com');
        $new = $request->withHeader('X-Foo', 'bar');
        $this->assertNotSame($request, $new);
        $this->assertSame('bar', $new->getHeaderLine('x-foo'));
        $this->assertFalse($request->hasHeader('X-Foo'));

        $this->expectException(InvalidArgumentException::class);
        $request->withHeader('X-Foo', "bar\r\ninjection");
    }
}
