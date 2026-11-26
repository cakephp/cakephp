<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Client\Auth;

use Cake\Http\Client\Request;
use SensitiveParameter;

/**
 * Contract for HTTP authentication strategies.
 */
interface AuthenticationInterface
{
    /**
     * Add authentication headers to a request.
     *
     * @param \Cake\Http\Client\Request $request The request to authenticate.
     * @param array<string, mixed> $credentials Authentication credentials.
     * @return \Cake\Http\Client\Request
     */
    public function authentication(Request $request, #[SensitiveParameter] array $credentials): Request;
}
