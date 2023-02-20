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
 * @since         5.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http;

use Cake\Http\StreamFactory;
use Cake\TestSuite\TestCase;

/**
 * Test case for the stream factory.
 */
class StreamFactoryTest extends TestCase
{
    protected StreamFactory $factory;

    protected string $filename = TMP . 'stream-factory-file-test.txt';

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new StreamFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // phpcs:disable
        @unlink($this->filename);
        // phpcs:enable
    }

    public function testCreateStream(): void
    {
        $stream = $this->factory->createStream('test');
        $this->assertSame('test', $stream->getContents());
    }

    public function testCreateStreamFile(): void
    {
        file_put_contents($this->filename, 'it works');

        $stream = $this->factory->createStreamFromFile($this->filename);
        $this->assertSame('it works', $stream->getContents());
    }

    public function testCreateStreamResource(): void
    {
        file_put_contents($this->filename, 'it works');
        $resource = fopen($this->filename, 'r');

        $stream = $this->factory->createStreamFromResource($resource);
        $this->assertSame('it works', $stream->getContents());

        fclose($resource);
    }
}
