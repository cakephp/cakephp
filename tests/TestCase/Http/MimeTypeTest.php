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
 * @since         5.2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Test\TestCase;

use Cake\Http\MimeType;
use Cake\TestSuite\TestCase;

class MimeTypeTest extends TestCase
{
    public function testGetMimeTypes(): void
    {
        $this->assertSame(['text/html', '*/*'], MimeType::getMimeTypes('html'));
        $this->assertSame(['application/json'], MimeType::getMimeTypes('json'));
        $this->assertNull(MimeType::getMimeTypes('unknown'));
    }

    public function testGetMimeType(): void
    {
        $this->assertSame('text/html', MimeType::getMimeType('html'));
        $this->assertSame('application/json', MimeType::getMimeType('json'));
        $this->assertNull(MimeType::getMimeType('unknown'));
    }

    public function testAddMimeTypes(): void
    {
        MimeType::addMimeTypes('html', 'foo/bar');
        $this->assertContains('foo/bar', MimeType::getMimeTypes('html'));

        MimeType::addMimeTypes('newext', ['application/new', 'text/new']);
        $this->assertSame(['application/new', 'text/new'], MimeType::getMimeTypes('newext'));
    }

    public function testSetMimeTypes(): void
    {
        MimeType::setMimeTypes('html', 'application/xhtml+xml');
        $this->assertSame(['application/xhtml+xml'], MimeType::getMimeTypes('html'));
        MimeType::setMimeTypes('html', ['text/html', '*/*']);

        MimeType::setMimeTypes('newext', ['application/new', 'text/new']);
        $this->assertSame(['application/new', 'text/new'], MimeType::getMimeTypes('newext'));
    }

    public function testGetExtension(): void
    {
        $this->assertSame('html', MimeType::getExtension('text/html'));
        $this->assertSame('json', MimeType::getExtension('application/json'));
        $this->assertNull(MimeType::getExtension('unknown/mime'));
    }

    public function testGetMimeTypeForFile(): void
    {
        $this->assertSame('application/json', MimeType::getMimeTypeForFile(CONFIG . 'json_test.json'));
        $this->assertSame('text/plain; charset=us-ascii', MimeType::getMimeTypeForFile(CONFIG . 'no_section.ini'));
    }
}
