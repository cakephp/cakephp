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
 * Declares a PATCH-only route for a controller action.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Patch extends Route
{
    /**
     * Initializes a PATCH route attribute definition.
     *
     * @param string $path Route path.
     * @param string|null $name Route name.
     * @param array<string, string> $patterns Route parameter patterns.
     * @param array<string, mixed> $defaults Route defaults.
     * @param array<string>|null $pass Passed arguments. When null, pass names are inferred.
     * @param array<string> $persist Persistent parameters.
     * @param string|null $host Host pattern.
     * @param string|null $routeClass Route class.
     */
    public function __construct(
        string $path,
        ?string $name = null,
        array $patterns = [],
        array $defaults = [],
        ?array $pass = null,
        array $persist = [],
        ?string $host = null,
        ?string $routeClass = null,
    ) {
        parent::__construct(
            path: $path,
            name: $name,
            methods: ['PATCH'],
            patterns: $patterns,
            defaults: $defaults,
            pass: $pass,
            persist: $persist,
            host: $host,
            routeClass: $routeClass,
        );
    }
}
