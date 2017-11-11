<?php
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
use Cake\TestSuite\TestCase;

/**
 * TestCacheStaticConfig
 */
class TestCacheStaticConfig
{

    use StaticConfigTrait;

    /**
     * Cache driver class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'apc' => 'Cake\Cache\Engine\ApcEngine',
        'file' => 'Cake\Cache\Engine\FileEngine',
        'memcached' => 'Cake\Cache\Engine\MemcachedEngine',
        'null' => 'Cake\Cache\Engine\NullEngine',
        'redis' => 'Cake\Cache\Engine\RedisEngine',
        'wincache' => 'Cake\Cache\Engine\WincacheEngine',
        'xcache' => 'Cake\Cache\Engine\XcacheEngine',
    ];
}

/**
 * TestEmailStaticConfig
 */
class TestEmailStaticConfig
{

    use StaticConfigTrait;

    /**
     * Email driver class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'debug' => 'Cake\Mailer\Transport\DebugTransport',
        'mail' => 'Cake\Mailer\Transport\MailTransport',
        'smtp' => 'Cake\Mailer\Transport\SmtpTransport',
    ];
}

/**
 * TestLogStaticConfig
 */
class TestLogStaticConfig
{

    use StaticConfigTrait;

    /**
     * Log engine class map.
     *
     * @var array
     */
    protected static $_dsnClassMap = [
        'console' => 'Cake\Log\Engine\ConsoleLog',
        'file' => 'Cake\Log\Engine\FileLog',
        'syslog' => 'Cake\Log\Engine\SyslogLog',
    ];
}

/**
 * StaticConfigTraitTest class
 */
class StaticConfigTraitTest extends TestCase
{

    /**
     * setup method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $this->subject = $this->getObjectForTrait('Cake\Core\StaticConfigTrait');
    }

    /**
     * teardown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->subject);
        parent::tearDown();
    }

    /**
     * Tests simple usage of parseDsn
     *
     * @return void
     */
    public function testSimpleParseDsn()
    {
        $className = get_class($this->subject);
        $this->assertSame([], $className::parseDsn(''));
    }

    /**
     * Tests that failing to pass a string to parseDsn will throw an exception
     *
     * @return void
     */
    public function testParseBadType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $className = get_class($this->subject);
        $className::parseDsn(['url' => 'http://:80']);
    }

    /**
     * Tests parsing querystring values
     *
     * @return void
     */
    public function testParseDsnQuerystring()
    {
        $dsn = 'file:///?url=test';
        $expected = [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => '/',
            'scheme' => 'file',
            'url' => 'test',
        ];
        $this->assertEquals($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'file:///?file=debug&key=value';
        $expected = [
            'className' => 'Cake\Log\Engine\FileLog',
            'file' => 'debug',
            'key' => 'value',
            'path' => '/',
            'scheme' => 'file',
        ];
        $this->assertEquals($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'file:///tmp?file=debug&types[]=notice&types[]=info&types[]=debug';
        $expected = [
            'className' => 'Cake\Log\Engine\FileLog',
            'file' => 'debug',
            'path' => '/tmp',
            'scheme' => 'file',
            'types' => ['notice', 'info', 'debug'],
        ];
        $this->assertEquals($expected, TestLogStaticConfig::parseDsn($dsn));

        $dsn = 'mail:///?timeout=30&key=true&key2=false&client=null&tls=null';
        $expected = [
            'className' => 'Cake\Mailer\Transport\MailTransport',
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
            'className' => 'Cake\Mailer\Transport\MailTransport',
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
            'className' => 'Cake\Mailer\Transport\MailTransport',
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
            'className' => 'Cake\Log\Engine\FileLog',
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
     *
     * @return void
     */
    public function testParseDsnPathSetting()
    {
        $dsn = 'file:///?path=/tmp/persistent/';
        $expected = [
            'className' => 'Cake\Log\Engine\FileLog',
            'path' => '/tmp/persistent/',
            'scheme' => 'file',
        ];
        $this->assertEquals($expected, TestLogStaticConfig::parseDsn($dsn));
    }

    /**
     * Test that the dsn map can be updated/append to
     *
     * @return void
     */
    public function testCanUpdateClassMap()
    {
        $expected = [
            'console' => 'Cake\Log\Engine\ConsoleLog',
            'file' => 'Cake\Log\Engine\FileLog',
            'syslog' => 'Cake\Log\Engine\SyslogLog',
        ];
        $result = TestLogStaticConfig::dsnClassMap();
        $this->assertEquals($expected, $result, 'The class map should match the class property');

        $expected = [
            'console' => 'Special\EngineLog',
            'file' => 'Cake\Log\Engine\FileLog',
            'syslog' => 'Cake\Log\Engine\SyslogLog',
        ];
        $result = TestLogStaticConfig::dsnClassMap(['console' => 'Special\EngineLog']);
        $this->assertEquals($expected, $result, 'Should be possible to change the map');

        $expected = [
            'console' => 'Special\EngineLog',
            'file' => 'Cake\Log\Engine\FileLog',
            'syslog' => 'Cake\Log\Engine\SyslogLog',
            'my' => 'Special\OtherLog'
        ];
        $result = TestLogStaticConfig::dsnClassMap(['my' => 'Special\OtherLog']);
        $this->assertEquals($expected, $result, 'Should be possible to add to the map');
    }

    /**
     * Tests that former handling of integer keys coming in from PHP internal conversions
     * won't break in 3.4.
     *
     * @return void
     */
    public function testConfigBC()
    {
        $result = TestLogStaticConfig::config(404);
        $this->assertNull($result);
    }
}
