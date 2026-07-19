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
namespace Cake\Utility\Fs\Iterator;

use Cake\Utility\Fs\Path;
use FilterIterator;
use Iterator;

/**
 * Filters files by filename using glob patterns.
 *
 * Supports glob patterns like:
 * - `*.php` - All PHP files
 * - `Test*.php` - Files starting with Test
 * - `{foo,bar}.php` - foo.php or bar.php
 *
 * Can be used to include or exclude files based on the $negate parameter:
 * - When $negate is false (default): includes files matching patterns
 * - When $negate is true: excludes files matching patterns
 *
 * @extends \FilterIterator<string, \SplFileInfo, \Iterator<string, \SplFileInfo>>
 */
final class FilenameFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator<mixed, \SplFileInfo> $iterator The iterator to filter
     * @param array<string> $patterns Glob patterns to match against
     * @param bool $negate When true, inverts the filter (excludes matching files)
     */
    public function __construct(
        Iterator $iterator,
        protected readonly array $patterns,
        protected readonly bool $negate = false,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $filename = $this->current()->getFilename();

        $matches = false;
        foreach ($this->patterns as $pattern) {
            if (Path::matches($pattern, $filename)) {
                $matches = true;
                break;
            }
        }

        return $this->negate ? !$matches : $matches;
    }
}
