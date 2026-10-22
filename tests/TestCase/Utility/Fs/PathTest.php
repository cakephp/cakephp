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
 * @since         5.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Utility\Fs;

use Cake\TestSuite\TestCase;
use Cake\Utility\Fs\Path;

/**
 * Path test case
 */
class PathTest extends TestCase
{
    public function testNormalize(): void
    {
        $this->assertSame('path/to/file', Path::normalize('path\to\file'));
        $this->assertSame('path/to/file', Path::normalize('path/to/file'));
        $this->assertSame('C:/Windows/System', Path::normalize('C:\Windows\System'));
    }

    public function testNormalizeWithTrailing(): void
    {
        $this->assertSame('path/to/dir', Path::normalize('path/to/dir/'));
        $this->assertSame('path/to/dir/', Path::normalize('path/to/dir/', trailing: true));
        $this->assertSame('path/to/dir/', Path::normalize('path/to/dir', trailing: true));
        $this->assertSame('/', Path::normalize('/', trailing: true));
        $this->assertSame('', Path::normalize('/'));
    }

    public function testIsAbsolute(): void
    {
        $this->assertTrue(Path::isAbsolute('/var/www'));
        $this->assertTrue(Path::isAbsolute('/'));
        $this->assertTrue(Path::isAbsolute('C:/Windows'));
        $this->assertTrue(Path::isAbsolute('C:\\Windows'));
        $this->assertTrue(Path::isAbsolute('/../src/Model'));

        $this->assertFalse(Path::isAbsolute('src/Model'));
        $this->assertFalse(Path::isAbsolute('./src/Model'));
        $this->assertFalse(Path::isAbsolute('../src/Model'));
        $this->assertFalse(Path::isAbsolute(''));

        // Lowercase drive letters are valid too
        $this->assertTrue(Path::isAbsolute('d:/folder'));

        // A drive letter without a following slash is drive-relative on Windows, not absolute
        $this->assertFalse(Path::isAbsolute('C:folder'));

        // UNC paths are absolute
        $this->assertTrue(Path::isAbsolute('\\\\server\\share'));
    }

    public function testMakeAbsolute(): void
    {
        $this->assertSame(
            '/var/www/src/file.php',
            Path::makeAbsolute('src/file.php', '/var/www'),
        );

        $this->assertSame(
            '/var/www/src/file.php',
            Path::makeAbsolute('/var/www/src/file.php', '/tmp'),
        );

        $this->assertSame(
            '/var/www',
            Path::makeAbsolute('', '/var/www'),
        );

        $this->assertSame(
            '/var/www/config',
            Path::makeAbsolute('../config', '/var/www/src'),
        );

        $this->assertSame(
            'C:/project/src/file.php',
            Path::makeAbsolute('src/file.php', 'C:/project'),
        );

        $this->assertSame(
            '/src/Model',
            Path::makeAbsolute('/../src/Model', '/var/www'),
        );
    }

    public function testMakeAbsoluteWithBackslashes(): void
    {
        // Absolute path with backslashes: base is ignored, separators are normalized.
        // This is the one branch not exercised by the existing tests.
        $this->assertSame(
            'C:/other/file.php',
            Path::makeAbsolute('C:\\other\\file.php', '/var/www'),
        );

        // Relative path with backslashes joined with a Unix base
        $this->assertSame(
            '/var/www/src/file.php',
            Path::makeAbsolute('src\\file.php', '/var/www'),
        );
    }

    public function testMakeAbsoluteResolvesDotSegments(): void
    {
        // Multiple ".." segments walk up more than one level
        $this->assertSame(
            '/var/etc/passwd',
            Path::makeAbsolute('../../etc/passwd', '/var/www/src'),
        );

        // "." segments are simply dropped
        $this->assertSame(
            '/var/www/src/file.php',
            Path::makeAbsolute('./src/./file.php', '/var/www'),
        );

        // ".." segments that would go above the root are dropped, not an error
        $this->assertSame(
            '/etc',
            Path::makeAbsolute('../../../../etc', '/var/www'),
        );

        // Already-absolute paths are resolved too, not just the joined ones
        $this->assertSame(
            '/var/etc/passwd',
            Path::makeAbsolute('/var/www/../etc/passwd', '/tmp'),
        );

        // Resolution also works on Windows-style drive paths
        $this->assertSame(
            'C:/var/www/config',
            Path::makeAbsolute('..\\config', 'C:\\var\\www\\src'),
        );

        // A single "." resolves to just the base
        $this->assertSame(
            '/var/www',
            Path::makeAbsolute('.', '/var/www'),
        );

        // ".." at the root stays at the root
        $this->assertSame(
            '/',
            Path::makeAbsolute('..', '/'),
        );
    }

    public function testMakeRelative(): void
    {
        $this->assertSame('src/Model/Table.php', Path::makeRelative('/var/www/src/Model/Table.php', '/var/www'));
        $this->assertSame('src/file.php', Path::makeRelative('/var/www/src/file.php', '/var/www/'));
        $this->assertSame('', Path::makeRelative('/var/www', '/var/www'));

        // Cross-platform
        $this->assertSame('src/file.php', Path::makeRelative('C:\project\src\file.php', 'C:\project'));

        // Going up directories
        $this->assertSame('../other/path', Path::makeRelative('/var/other/path', '/var/www'));
    }

    public function testJoin(): void
    {
        $this->assertSame('path/to/file', Path::join('path', 'to', 'file'));
        $this->assertSame('/absolute/path/file', Path::join('/absolute', 'path', 'file'));
        $this->assertSame('path/to/file', Path::join('path/', '/to/', '/file'));
        $this->assertSame('path/to/file', Path::join('path\\', '\\to\\', '\\file'));
        $this->assertSame('', Path::join());
        $this->assertSame('path', Path::join('path'));
        $this->assertSame('path/file', Path::join('path', '', 'file'));
    }

    public function testMatches(): void
    {
        $this->assertTrue(Path::matches('*.php', 'file.php'));
        $this->assertFalse(Path::matches('*.php', 'file.txt'));
        $this->assertTrue(Path::matches('src/**/*.php', 'src/Model/Table.php'));
        $this->assertTrue(Path::matches('vendor/**', 'vendor/lib/file.php'));
        $this->assertFalse(Path::matches('tests/**', 'src/file.php'));
        $this->assertTrue(Path::matches('src/*/Table.php', 'src/Model/Table.php'));
        $this->assertTrue(Path::matches('**/file.php', 'deep/nested/path/file.php'));
    }
}
