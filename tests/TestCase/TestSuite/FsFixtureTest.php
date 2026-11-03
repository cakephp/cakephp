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
 * @since         5.4.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\TestSuite;

use Cake\TestSuite\FsFixture;
use Cake\TestSuite\TestCase;

class FsFixtureTest extends TestCase
{
    public function testSetupRemovesDirectorySymlinkWithoutRemovingTarget(): void
    {
        $target = FsFixture::setup('symlink-target', ['keep.txt' => 'keep']);
        $root = FsFixture::setup('symlink-root');
        try {
            // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged -- Skip platforms without symlink permissions.
            if (!@symlink($target, $root . DS . 'alias')) {
                $this->markTestSkipped('Cannot create directory symlinks.');
            }

            FsFixture::setup('symlink-root', ['new.txt' => 'new']);

            $this->assertFileDoesNotExist($root . DS . 'alias');
            $this->assertSame('new', file_get_contents($root . DS . 'new.txt'));
            $this->assertSame('keep', file_get_contents($target . DS . 'keep.txt'));
        } finally {
            FsFixture::tearDown();
        }
    }
}
