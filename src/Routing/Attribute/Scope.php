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
namespace Cake\Routing\Attribute;

use Attribute;

/**
 * Declares shared path, name, and option defaults for controller routes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Scope
{
    /**
     * Initializes a scope attribute definition.
     *
     * @param string $path Path prefix.
     * @param string $namePrefix Route name prefix.
     * @param array<string, mixed> $defaults Default route values.
     * @param array<string, string> $patterns Shared route patterns.
     * @param string|null $host Host pattern.
     */
    public function __construct(
        public string $path = '',
        public string $namePrefix = '',
        public array $defaults = [],
        public array $patterns = [],
        public ?string $host = null,
    ) {
    }
}
