<?php
declare(strict_types=1);

namespace Controller;

use Cake\Controller\RequestParamController;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\CookieCollection;
use Cake\Http\ServerRequest;
use PHPUnit\Framework\TestCase;

class RequestParamTraitTest extends TestCase
{
    /**
     * @dataProvider getQueryIntProvider
     */
    public function testGetQueryInt(string $query, ?int $expected): void
    {
        $result = $this->getControllerWithQuery($query)->getQueryInt('test');
        $this->assertSame($expected, $result);
    }

    public static function getQueryIntProvider(): array
    {
        return [
            'empty query' => ['', null],
            'missing' => ['other-test=', null],
            'empty' => ['test=', null],
            'space' => ['test= ', null],
            'null' => ['test=null', null],
            'dash' => ['test=-', null],
            'ctz' => ['test=čťž', null],
            'hex' => ['test=0x539', null],
            'binary' => ['test=0b10100111001', null],
            'zero' => ['test=0', 0],
            'number' => ['test=55', 55],
            'number_space_before' => ['test= 55', 55],
            'number_space_after' => ['test=55 ', 55],
            'negative number' => ['test=-5', -5],
            'float round' => ['test=5.0', null],
            'float real' => ['test=5.1', null],
            'float round slovak' => ['test=5,0', null],
            'int ok-overflow' => ['test=9223372036854775807', 9223372036854775807],
            'int overflow' => ['test=9223372036854775808', null],
            'int-negative ok-overflow' => ['test=-9223372036854775807', -9223372036854775807],
            //-9223372036854775808 - PHP inconsistency (once float, once int)
            'int-negative overflow' => ['test=-9223372036854775809', null],
            'string' => ['test=f', null],
            'partially1 number' => ['test=5 5', null],
            'partially2 number' => ['test=5x', null],
            'partially3 number' => ['test=x4', null],
            'empty-array' => ['test[]=', null],
            'int-array' => ['test[]=5', null],
            'int-one-array' => ['test[1]=5', null],
            'string-one-array' => ['test[1]=h', null],
        ];
    }

    /**
     * @dataProvider getIntProvider
     */
    public function testGetDataInt(mixed $rawValue, ?int $expected): void
    {
        $result = $this->getControllerWithData('test', $rawValue)->getDataInt('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getIntProvider
     */
    public function testGetParamInt(mixed $rawValue, ?int $expected): void
    {
        $result = $this->getControllerWithParam('test', $rawValue)->getParamInt('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getIntProvider
     */
    public function testGetCookieInt(mixed $rawValue, ?int $expected): void
    {
        $result = $this->getControllerWithCookie('test', $rawValue)->getCookieInt('test');
        $this->assertSame($expected, $result);
    }

    public static function getIntProvider(): array
    {
        return [
            // like string
            '(string) empty' => ['', null],
            '(string) space' => [' ', null],
            '(string) null' => ['null', null],
            '(string) dash' => ['-', null],
            '(string) ctz' => ['čťž', null],
            '(string) hex' => ['0x539', null],
            '(string) binary' => ['0b10100111001', null],
            '(string) zero' => ['0', 0],
            '(string) number' => ['55', 55],
            '(string) number_space_before' => [' 55', 55],
            '(string) number_space_after' => ['55 ', 55],
            '(string) negative number' => ['-5', -5],
            '(string) float round' => ['5.0', null],
            '(string) float real' => ['5.1', null],
            '(string) float round slovak' => ['5,0', null],
            '(string) int ok-overflow' => ['9223372036854775807', 9223372036854775807],
            '(string) int overflow' => ['9223372036854775808', null],
            '(string) int-negative ok-overflow' => ['-9223372036854775807', -9223372036854775807],
            // -9223372036854775808 - PHP inconsistency (once float, once int)
            '(string) int-negative overflow' => ['-9223372036854775809', null],
            '(string) string' => ['f', null],
            '(string) partially1 number' => ['5 5', null],
            '(string) partially2 number' => ['5x', null],
            '(string) partially3 number' => ['x4', null],
            // like int
            '(int) number' => [55, 55],
            '(int) negative number' => [-5, -5],
            '(int) int ok-overflow' => [9223372036854775807, 9223372036854775807],
            '(int) int overflow' => [9223372036854775808, null],
            '(int) int-negative ok-overflow' => [-9223372036854775807, -9223372036854775807],
            '(int) int-negative overflow' => [-9223372036854775809, null],
            // other
            'empty-array' => [[], null],
            'int-array' => [[5], null],
            'int-one-array' => [[1 => 5], null],
            'string-one-array' => [[1 => 'h'], null],
            'float' => [5.5, null],
            'float round' => [5.0, 5],
            'float negative' => [-5.5, null],
            'float round negative' => [-5.0, -5],
        ];
    }

    /**
     * @dataProvider getQueryStringProvider
     */
    public function testGetQueryString(string $query, ?string $expected): void
    {
        $result = $this->getControllerWithQuery($query)->getQueryString('test');
        $this->assertSame($expected, $result);
    }

    public static function getQueryStringProvider(): array
    {
        return [
            'empty query' => ['', null],
            'missing' => ['other-test=', null],
            'empty' => ['test=', ''],
            'space' => ['test= ', ' '],
            'null' => ['test=null', 'null'],
            'dash' => ['test=-', '-'],
            'ctz' => ['test=čťž', 'čťž'],
            'hex' => ['test=0x539', '0x539'],
            'binary' => ['test=0b10100111001', '0b10100111001'],
            'zero' => ['test=0', '0'],
            'number' => ['test=55', '55'],
            'number_space_before' => ['test= 55', ' 55'],
            'number_space_after' => ['test=55 ', '55 '],
            'negative number' => ['test=-5', '-5'],
            'float round' => ['test=5.0', '5.0'],
            'float real' => ['test=5.1', '5.1'],
            'float round slovak' => ['test=5,0', '5,0'],
            'int ok-overflow' => ['test=9223372036854775807', '9223372036854775807'],
            'int overflow' => ['test=9223372036854775808', '9223372036854775808'],
            'int-negative ok-overflow' => ['test=-9223372036854775807', '-9223372036854775807'],
            'int-negative overflow' => ['test=-9223372036854775809', '-9223372036854775809'],
            'string' => ['test=f', 'f'],
            'partially1 number' => ['test=5 5', '5 5'],
            'partially2 number' => ['test=5x', '5x'],
            'partially3 number' => ['test=x4', 'x4'],
            'empty-array' => ['test[]=', null],
            'int-array' => ['test[]=5', null],
            'int-one-array' => ['test[1]=5', null],
            'string-one-array' => ['test[1]=h', null],
        ];
    }

    /**
     * @dataProvider getStringProvider
     */
    public function testGetDataString(mixed $value, ?string $expected): void
    {
        $result = $this->getControllerWithData('test', $value)->getDataString('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getStringProvider
     */
    public function testGetParamString(mixed $value, ?string $expected): void
    {
        $result = $this->getControllerWithParam('test', $value)->getParamString('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getStringProvider
     */
    public function testGetCookieString(mixed $value, ?string $expected): void
    {
        $result = $this->getControllerWithCookie('test', $value)->getCookieString('test');
        $this->assertSame($expected, $result);
    }

    public static function getStringProvider(): array
    {
        return [
            'empty' => ['', ''],
            'space' => [' ', ' '],
            'dash' => ['-', '-'],
            'zero' => ['0', '0'],
            'number' => ['55', '55'],
            'partially2 number' => ['5x', '5x'],
            'string-array' => [['5'], null],
        ];
    }

    /**
     * @dataProvider getQueryBoolProvider
     */
    public function testGetQueryBool(string $query, ?bool $expected): void
    {
        $result = $this->getControllerWithQuery($query)->getQueryBool('test');
        $this->assertSame($expected, $result, 'query: ' . $query);
    }

    public static function getQueryBoolProvider(): array
    {
        return [
            'empty string' => ['test=', null],
            'space' => ['test= ', null],
            'some word' => ['test=abc', null],
            'double 0' => ['test=00', null],
            'single 0' => ['test=0', false],
            'false' => ['test=false', null],
            'double 1' => ['test=11', null],
            'single 1' => ['test=1', true],
            'true' => ['test=true', null],
            'empty-array' => ['test[]=', null],
            'int-array' => ['test[]=1', null],
            'int-one-array' => ['test[1]=true', null],
            'string-one-array' => ['test[1]=0', null],
        ];
    }

    /**
     * @dataProvider getBoolProvider
     */
    public function testGetDataBool(mixed $rawValue, ?bool $expected): void
    {
        $result = $this->getControllerWithData('test', $rawValue)->getDataBool('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getBoolProvider
     */
    public function testGetParamBool(mixed $rawValue, ?bool $expected): void
    {
        $result = $this->getControllerWithParam('test', $rawValue)->getParamBool('test');
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getBoolProvider
     */
    public function testGetCookieBool(mixed $rawValue, ?bool $expected): void
    {
        $result = $this->getControllerWithCookie('test', $rawValue)->getCookieBool('test');
        $this->assertSame($expected, $result);
    }

    public static function getBoolProvider(): array
    {
        return [
            'empty string' => ['', null],
            'space' => [' ', null],
            'some word' => ['abc', null],
            'double 0' => ['00', null],
            'single 0' => ['0', false],
            'false' => ['false', null],
            'double 1' => ['11', null],
            'single 1' => ['1', true],
            'true-string' => ['true', null],
            'true' => [true, true],
            'int 0' => [0, false],
            'int 1' => [1, true],
            'int -1' => [-1, null],
            'int 55' => [55, null],
            'negative number' => [-5, null],
            'empty-array' => [[], null],
            'int-array' => [[5], null],
            'int-one-array' => [[1 => 5], null],
            'string-one-array' => [[1 => 'h'], null],
            'float' => [5.5, null],
            'float round' => [5.0, null],
            'float 0.0' => [0.0, false],
            'float 1.0' => [1.0, true],
        ];
    }

    private function getControllerWithQuery(string $query): RequestParamController
    {
        $request = new ServerRequest(['url' => '/some/url?' . $query]);

        return new RequestParamController($request);
    }

    private function getControllerWithData(string $name, mixed $value): RequestParamController
    {
        $request = (new ServerRequest())->withData($name, $value);

        return new RequestParamController($request);
    }

    private function getControllerWithParam(string $name, mixed $value): RequestParamController
    {
        $request = (new ServerRequest())->withParam($name, $value);

        return new RequestParamController($request);
    }

    private function getControllerWithCookie(string $name, array|string|float|int|bool $value): RequestParamController
    {
        $request = (new ServerRequest())->withCookieCollection(new CookieCollection([new Cookie($name, $value)]));

        return new RequestParamController($request);
    }
}
