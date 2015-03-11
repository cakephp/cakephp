<?php
/**
 * FileLogTest file
 *
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @since         1.3.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Log\Engine;

use Cake\Log\Engine\FileLog;
use Cake\TestSuite\TestCase;
use JsonSerializable;

/**
 * Class used for testing when an object is passed to a logger
 *
 */
class StringObject
{

    /**
     * String representation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return 'Hey!';
    }
}

/**
 * Class used for testing when an serializable is passed to a logger
 *
 */
class JsonObject implements JsonSerializable
{

    /**
     * String representation of the object
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return ['hello' => 'world'];
    }
}

/**
 * FileLogTest class
 *
 */
class FileLogTest extends TestCase
{

    /**
     * testLogFileWriting method
     *
     * @return void
     */
    public function testLogFileWriting()
    {
        $this->_deleteLogs(LOGS);

        $log = new FileLog(['path' => LOGS]);
        $log->log('warning', 'Test warning');
        $this->assertTrue(file_exists(LOGS . 'error.log'));

        $result = file_get_contents(LOGS . 'error.log');
        $this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Warning: Test warning/', $result);

        $log->log('debug', 'Test warning');
        $this->assertTrue(file_exists(LOGS . 'debug.log'));

        $result = file_get_contents(LOGS . 'debug.log');
        $this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Test warning/', $result);

        $log->log('random', 'Test warning');
        $this->assertTrue(file_exists(LOGS . 'random.log'));

        $result = file_get_contents(LOGS . 'random.log');
        $this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Random: Test warning/', $result);

        $object = new StringObject;
        $log->log('debug', $object);
        $this->assertTrue(file_exists(LOGS . 'debug.log'));
        $result = file_get_contents(LOGS . 'debug.log');
        $this->assertContains('Debug: Hey!', $result);

        $object = new JsonObject;
        $log->log('debug', $object);
        $this->assertTrue(file_exists(LOGS . 'debug.log'));
        $result = file_get_contents(LOGS . 'debug.log');
        $this->assertContains('Debug: ' . json_encode(['hello' => 'world']), $result);

        $log->log('debug', [1, 2]);
        $this->assertTrue(file_exists(LOGS . 'debug.log'));
        $result = file_get_contents(LOGS . 'debug.log');
        $this->assertContains('Debug: ' . print_r([1, 2], true), $result);
    }

    /**
     * test using the path setting to log logs in other places.
     *
     * @return void
     */
    public function testPathSetting()
    {
        $path = TMP . 'tests' . DS;
        $this->_deleteLogs($path);

        $log = new FileLog(compact('path'));
        $log->log('warning', 'Test warning');
        $this->assertTrue(file_exists($path . 'error.log'));
    }

    /**
     * test log rotation
     *
     * @return void
     */
    public function testRotation()
    {
        $path = TMP . 'tests' . DS;
        $this->_deleteLogs($path);

        file_put_contents($path . 'error.log', "this text is under 35 bytes\n");
        $log = new FileLog([
            'path' => $path,
            'size' => 35,
            'rotate' => 2
        ]);
        $log->log('warning', 'Test warning one');
        $this->assertTrue(file_exists($path . 'error.log'));

        $result = file_get_contents($path . 'error.log');
        $this->assertRegExp('/Warning: Test warning one/', $result);
        $this->assertEquals(0, count(glob($path . 'error.log.*')));

        clearstatcache();
        $log->log('warning', 'Test warning second');

        $files = glob($path . 'error.log.*');
        $this->assertEquals(1, count($files));

        $result = file_get_contents($files[0]);
        $this->assertRegExp('/this text is under 35 bytes/', $result);
        $this->assertRegExp('/Warning: Test warning one/', $result);

        sleep(1);
        clearstatcache();
        $log->log('warning', 'Test warning third');

        $result = file_get_contents($path . 'error.log');
        $this->assertRegExp('/Warning: Test warning third/', $result);

        $files = glob($path . 'error.log.*');
        $this->assertEquals(2, count($files));

        $result = file_get_contents($files[0]);
        $this->assertRegExp('/this text is under 35 bytes/', $result);

        $result = file_get_contents($files[1]);
        $this->assertRegExp('/Warning: Test warning second/', $result);

        file_put_contents($path . 'error.log.0000000000', "The oldest log file with over 35 bytes.\n");

        sleep(1);
        clearstatcache();
        $log->log('warning', 'Test warning fourth');

        // rotate count reached so file count should not increase
        $files = glob($path . 'error.log.*');
        $this->assertEquals(2, count($files));

        $result = file_get_contents($path . 'error.log');
        $this->assertRegExp('/Warning: Test warning fourth/', $result);

        $result = file_get_contents(array_pop($files));
        $this->assertRegExp('/Warning: Test warning third/', $result);

        $result = file_get_contents(array_pop($files));
        $this->assertRegExp('/Warning: Test warning second/', $result);

        file_put_contents($path . 'debug.log', "this text is just greater than 35 bytes\n");
        $log = new FileLog([
            'path' => $path,
            'size' => 35,
            'rotate' => 0
        ]);
        file_put_contents($path . 'debug.log.0000000000', "The oldest log file with over 35 bytes.\n");
        $log->log('debug', 'Test debug');
        $this->assertTrue(file_exists($path . 'debug.log'));

        $result = file_get_contents($path . 'debug.log');
        $this->assertRegExp('/^2[0-9]{3}-[0-9]+-[0-9]+ [0-9]+:[0-9]+:[0-9]+ Debug: Test debug/', $result);
        $this->assertFalse(strstr($result, 'greater than 5 bytes'));
        $this->assertEquals(0, count(glob($path . 'debug.log.*')));
    }

    public function testMaskSetting()
    {
        if (DS === '\\') {
            $this->markTestSkipped('File permission testing does not work on Windows.');
        }

        $path = TMP . 'tests' . DS;
        $this->_deleteLogs($path);

        $log = new FileLog(['path' => $path, 'mask' => 0666]);
        $log->log('warning', 'Test warning one');
        $result = substr(sprintf('%o', fileperms($path . 'error.log')), -4);
        $expected = '0666';
        $this->assertEquals($expected, $result);
        unlink($path . 'error.log');

        $log = new FileLog(['path' => $path, 'mask' => 0644]);
        $log->log('warning', 'Test warning two');
        $result = substr(sprintf('%o', fileperms($path . 'error.log')), -4);
        $expected = '0644';
        $this->assertEquals($expected, $result);
        unlink($path . 'error.log');

        $log = new FileLog(['path' => $path, 'mask' => 0640]);
        $log->log('warning', 'Test warning three');
        $result = substr(sprintf('%o', fileperms($path . 'error.log')), -4);
        $expected = '0640';
        $this->assertEquals($expected, $result);
        unlink($path . 'error.log');
    }

    /**
     * helper function to clears all log files in specified directory
     *
     * @param string $dir
     * @return void
     */
    protected function _deleteLogs($dir)
    {
        $files = array_merge(glob($dir . '*.log'), glob($dir . '*.log.*'));
        foreach ($files as $file) {
            unlink($file);
        }
    }
}
