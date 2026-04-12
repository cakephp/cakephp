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
 * Filters files by matching glob patterns against relative paths.
 *
 * Supports patterns like:
 * - `src/**\/*.php` - Recursive matching
 * - `tests/**\/*Test.php` - Files ending with Test.php
 * - `*.md` - Files in root directory
 *
 * @extends \FilterIterator<string, \SplFileInfo, \Iterator<string, \SplFileInfo>>
 */
final class GlobFilterIterator extends FilterIterator
{
    /**
     * @param \Iterator $iterator The iterator to filter
     * @param array<string> $patterns Glob patterns to match
     * @param string $basePath Base path to calculate relative paths from
     */
    public function __construct(
        Iterator $iterator,
        protected readonly array $patterns,
        protected readonly string $basePath,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @inheritDoc
     */
    public function accept(): bool
    {
        $relativePath = Path::makeRelative(
            $this->current()->getPathname(),
            $this->basePath,
        );

        foreach ($this->patterns as $pattern) {
            if (Path::matches($pattern, $relativePath)) {
                return true;
            }
        }

        return false;
    }
}
