<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\TestSuite\Constraint;

use PHPUnit\Framework\Constraint\Constraint;

/**
 * Base constraint for content constraints
 *
 * @internal
 */
abstract class ContentsBase extends Constraint
{
    /**
     * @var string
     */
    protected $contents;

    /**
     * @var string
     */
    protected $output;

    /**
     * Constructor
     *
     * @param array<string> $contents Contents
     * @param string $output Output type
     */
    public function __construct(array $contents, string $output)
    {
        $this->contents = implode(PHP_EOL, $contents);
        $this->output = $output;
    }
}
