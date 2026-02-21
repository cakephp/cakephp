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
 * Declares REST resource routes for a controller.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Resource
{
    /**
     * Initializes a resource attribute definition.
     *
     * @param string|null $path Optional resource path.
     * @param array<string> $only Resource actions to include.
     * @param array<string, string> $actions Custom action mappings.
     * @param array<string, array<string, mixed>> $map Additional route mappings.
     * @param string|null $prefix Optional prefix for the resource routes.
     * @param string $id Identifier regex pattern.
     * @param string $inflect Inflection method used for path generation.
     * @param array<string, mixed> $connectOptions Options forwarded to connect().
     */
    public function __construct(
        public ?string $path = null,
        public array $only = [],
        public array $actions = [],
        public array $map = [],
        public ?string $prefix = null,
        public string $id = '',
        public string $inflect = 'dasherize',
        public array $connectOptions = [],
    ) {
    }
}
