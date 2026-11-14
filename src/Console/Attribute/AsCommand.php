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
use InvalidArgumentException;

/**
 * Declares a command for discovery through an attribute resolver collection.
 *
 * The name is relative to the executable, for example `users export`.
 * Description is used in listings; help adds lines to the command's description.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class AsCommand
{
    /**
     * Define command identity and help content.
     *
     * @param string $name Command name, containing at most three words.
     * @param string $description Summary displayed in command listings.
     * @param array<string>|string|null $help Additional description lines.
     * @param array<string>|string|null $epilog Text displayed after arguments and options.
     */
    public function __construct(
        public string $name,
        public string $description = '',
        public array|string|null $help = null,
        public array|string|null $epilog = null,
    ) {
        if (!preg_match('/^[^\\s]+(?:(?: [^\\s]+){1,2})?$/ui', $name)) {
            throw new InvalidArgumentException(
                "The command name `{$name}` is invalid. Names can only be a maximum of three words.",
            );
        }
    }
}
