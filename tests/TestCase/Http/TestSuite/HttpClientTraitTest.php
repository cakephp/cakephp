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
 * @since         4.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\TestSuite;

use Cake\Http\Client;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;

class HttpClientTraitTest extends TestCase
{
    use HttpClientTrait;

    /**
     * Provider for http methods.
     *
     * @return array<array>
     */
    public static function methodProvider(): array
    {
        return [
            ['Get'],
            ['Post'],
            ['Put'],
            ['Patch'],
            ['Delete'],
        ];
    }

    /**
     * @dataProvider methodProvider
     */
    public function testRequestMethods(string $httpMethod)
    {
        $traitMethod = "mockClient{$httpMethod}";

        $response = $this->newClientResponse(200, ['Content-Type: application/json'], '{"ok":true}');
        $this->{$traitMethod}('http://example.com', $response);

        $client = new Client();
        $result = $client->{$httpMethod}('http://example.com');
        $this->assertSame($response, $result);
    }
}
