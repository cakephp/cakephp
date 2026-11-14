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
 * Defines a named option using the existing console parser semantics.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class Option
{
    /**
     * Define a named option.
     *
     * @param string $name Long option name without leading dashes.
     * @param string $short Single-character alias.
     * @param string $help Option help text.
     * @param bool $boolean Whether the option is a flag that consumes no value.
     * @param string|bool|null $default Value used when the option is omitted.
     * @param array<string> $choices Allowed values.
     * @param bool $multiple Whether repeated values are accepted.
     * @param bool $required Whether the option must be supplied.
     * @param string|null $prompt Interactive prompt used when a value is missing.
     * @param string|null $separator Separator for multiple values.
     */
    public function __construct(
        public string $name,
        public string $short = '',
        public string $help = '',
        public bool $boolean = false,
        public string|bool|null $default = null,
        public array $choices = [],
        public bool $multiple = false,
        public bool $required = false,
        public ?string $prompt = null,
        public ?string $separator = null,
    ) {
    }
}
