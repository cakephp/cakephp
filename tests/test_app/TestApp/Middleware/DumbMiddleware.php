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
 * @since         3.3.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Testing stub for middleware tests.
 */
class DumbMiddleware
{
    public function __invoke(ServerRequestInterface $req, ResponseInterface $res, callable $next)
    {
        return $next($req, $res);
    }
}
