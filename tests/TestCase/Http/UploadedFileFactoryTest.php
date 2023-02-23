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

use Cake\Http\UploadedFileFactory;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\Stream;

/**
 * Test case for the uploaded file factory.
 */
class UploadedFileFactoryTest extends TestCase
{
    protected UploadedFileFactory $factory;

    protected string $filename = TMP . 'uploadedfile-factory-file-test.txt';

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new UploadedFileFactory();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // phpcs:disable
        @unlink($this->filename);
        // phpcs:enable
    }

    public function testCreateStreamResource(): void
    {
        file_put_contents($this->filename, 'it works');
        $stream = new Stream($this->filename);

        $uploadedFile = $this->factory->createUploadedFile($stream, null, UPLOAD_ERR_OK, 'my-name');
        $this->assertSame('my-name', $uploadedFile->getClientFilename());
        $this->assertSame($stream, $uploadedFile->getStream());
    }
}
