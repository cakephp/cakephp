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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Console\Attribute;

use Attribute;

/**
 * Defines a positional argument using the existing console parser semantics.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class Argument
{
    /**
     * Define a positional argument.
     *
     * @param string $name Argument name used by Arguments::getArgument().
     * @param string $help Argument help text.
     * @param bool $required Whether a value must be supplied.
     * @param array<string> $choices Allowed values.
     * @param string|null $default Value used when the argument is omitted.
     * @param string|null $separator Separator for multiple values.
     * @param int|null $index Explicit position, or null for declaration order.
     */
    public function __construct(
        public string $name,
        public string $help = '',
        public bool $required = false,
        public array $choices = [],
        public ?string $default = null,
        public ?string $separator = null,
        public ?int $index = null,
    ) {
    }
}
