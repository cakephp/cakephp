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
 * @since         5.4.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Datasource\Paging;

use Cake\Datasource\Paging\CursorEncoder;
use Cake\Datasource\Paging\Exception\InvalidCursorException;
use Cake\TestSuite\TestCase;

class CursorEncoderTest extends TestCase
{
    protected string $secret = 'test-cursor-secret-please-use-a-real-salt-in-prod-0123456789';

    public function testRoundTripPreservesScalarTypes(): void
    {
        $cursor = [
            'Articles.created' => '2025-01-02 03:04:05',
            'Articles.id' => 42,
            'Articles.score' => 3.14,
            'Articles.featured' => true,
            'Articles.subtitle' => null,
        ];

        $token = CursorEncoder::encode($cursor, $this->secret);
        $decoded = CursorEncoder::decode($token, $this->secret);

        $this->assertSame($cursor, $decoded);
    }

    public function testTokenIsUrlSafe(): void
    {
        $cursor = ['Articles.id' => 1];
        $token = CursorEncoder::encode($cursor, $this->secret);

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$/', $token);
        $this->assertStringNotContainsString('=', $token);
    }

    public function testTamperedPayloadIsRejected(): void
    {
        $token = CursorEncoder::encode(['id' => 1], $this->secret);
        $sig = explode('.', $token, 2)[1];

        $tampered = rtrim(strtr(base64_encode('{"id":999}'), '+/', '-_'), '=') . '.' . $sig;

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('signature is invalid');
        CursorEncoder::decode($tampered, $this->secret);
    }

    public function testMismatchedSecretIsRejected(): void
    {
        $token = CursorEncoder::encode(['id' => 1], $this->secret);

        $this->expectException(InvalidCursorException::class);
        CursorEncoder::decode($token, 'different-secret');
    }

    public function testMalformedTokenIsRejected(): void
    {
        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('malformed');
        CursorEncoder::decode('not-a-valid-token', $this->secret);
    }

    public function testEmptyTokenIsRejected(): void
    {
        $this->expectException(InvalidCursorException::class);
        CursorEncoder::decode('', $this->secret);
    }
}
