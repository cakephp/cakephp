<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Core;

use Cake\Core\StaticConfigTrait;
use Cake\Log\Engine\FileLog;
use Cake\Mailer\Transport\MailTransport;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use TestApp\Config\TestEmailStaticConfig;
use TestApp\Config\TestLogStaticConfig;
use TypeError;

/**
 * StaticConfigTraitTest class
 */
class StaticConfigTraitTest extends TestCase
{
    /**
     * @var object
     */
    protected $subject;

    /**
     * setup method
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new class {
            use StaticConfigTrait;
        };
    }

    /**
     * teardown method
     */
    protected function tearDown(): void
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * Tests simple usage of parseDsn
     */
    public function testSimpleParseDsn(): void
    {
        $className = $this->subject::class;
        $this->assertSame([], $className::parseDsn(''));
    }

    /**
     * Tests that failing to pass a string to parseDsn will throw an exception
     */
    public function testParseBadType(): void
    {
        $this->expectException(TypeError::class);
        $className = $this->subject::class;
        $className::parseDsn(['url' => 'http://:80']);
    }

    public function testSetConfigValues(): void
    {
        $className = $this->subject::class;
        $className::setConfig('foo', true);

        $result = $className::getConfigOrFail('foo');
        $this->assertTrue($result);

        $className::setConfig('bar', 'value');
        $result = $className::getConfigOrFail('bar');
        $this->assertSame('value', $result);
    }

    public function testGetConfigOrFail(): void
    {
        $className = $this->subject::class;
        $className::setConfig('foo2', ['bar' => true]);

        $result = $className::getConfigOrFail('foo2');
        $this->assertSame(['bar' => true], $result);
    }

    public function testGetConfigOrFailException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected configuration `unknown` not found.');

        $className = $this->subject::class;
        $className::getConfigOrFail('unknown');
    }

    /**
     * Tests parsing querystring values
     */
    public function testParseDsnQuerystring(): void
    {
        $dsn = 'file:///?url=test';
        $expected = [
            'className' => FileLog::class,
            'path' => '/',
            'scheme' => 'file',
            'url' => 'test',
        ];
        $this->assertSame($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'file:///?file=debug&key=value';
        $expected = [
            'className' => FileLog::class,
            'file' => 'debug',
            'key' => 'value',
            'path' => '/',
            'scheme' => 'file',
        ];
        $this->assertSame($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'file:///tmp?file=debug&types[]=notice&types[]=info&types[]=debug';
        $expected = [
            'className' => FileLog::class,
            'file' => 'debug',
            'path' => '/tmp',
            'scheme' => 'file',
            'types' => ['notice', 'info', 'debug'],
        ];
        $this->assertSame($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'mail:///?timeout=30&key=true&key2=false&client=null&tls=null';
        $expected = [
            'className' => MailTransport::class,
            'client' => null,
            'key' => true,
            'key2' => false,
            'path' => '/',
            'scheme' => 'mail',
            'timeout' => '30',
            'tls' => null,
        ];
        $this->assertEquals($expected, TestEmailStaticConfig::parseDsn($dsn));

        $dsn = 'mail://true:false@null/1?timeout=30&key=true&key2=false&client=null&tls=null';
        $expected = [
            'className' => MailTransport::class,
            'client' => null,
            'host' => 'null',
            'key' => true,
            'key2' => false,
            'password' => 'false',
            'path' => '/1',
            'scheme' => 'mail',
            'timeout' => '30',
            'tls' => null,
            'username' => 'true',
        ];
        $this->assertEquals($expected, TestEmailStaticConfig::parseDsn($dsn));

        $dsn = 'mail://user:secret@localhost:25?timeout=30&client=null&tls=null#fragment';
        $expected = [
            'className' => MailTransport::class,
            'client' => null,
            'host' => 'localhost',
            'password' => 'secret',
            'port' => 25,
            'scheme' => 'mail',
            'timeout' => '30',
            'tls' => null,
            'username' => 'user',
            'fragment' => 'fragment',
        ];
        $this->assertEquals($expected, TestEmailStaticConfig::parseDsn($dsn));

        $dsn = 'file:///?prefix=myapp_cake_core_&serialize=true&duration=%2B2 minutes';
        $expected = [
            'className' => FileLog::class,
            'duration' => '+2 minutes',
            'path' => '/',
            'prefix' => 'myapp_cake_core_',
            'scheme' => 'file',
            'serialize' => true,
        ];
        $this->assertEquals($expected, TestLogStaticConfig::parseDsn($dsn));
    }

    /**
     * Tests loading a single plugin
     */
    public function testParseDsnPathSetting(): void
    {
        $dsn = 'file:///?path=/tmp/persistent/';
        $expected = [
            'className' => FileLog::class,
            'path' => '/tmp/persistent/',
            'scheme' => 'file',
        ];
        $this->assertSame($expected, TestLogStaticConfig::parseDsn($dsn));
    }
}
