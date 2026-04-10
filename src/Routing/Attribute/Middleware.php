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
use Closure;

/**
 * Declares middleware names or inline closures that should be applied to matching routes.
 *
 * String arguments reference registered middleware or middleware group names.
 * Closure arguments define inline middleware with the signature:
 * `static function(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface`
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
readonly class Middleware
{
    /**
     * @var array<string>
     */
    public array $names;

    /**
     * @var array<\Closure>
     */
    public array $closures;

    /**
     * Initializes a middleware attribute definition.
     *
     * @param \Closure|string ...$middleware Middleware names, group names, or inline closures.
     */
    public function __construct(Closure|string ...$middleware)
    {
        $names = [];
        $closures = [];
        foreach ($middleware as $item) {
            if ($item instanceof Closure) {
                $closures[] = $item;
            } else {
                $names[] = $item;
            }
        }
        $this->names = $names;
        $this->closures = $closures;
    }
}
