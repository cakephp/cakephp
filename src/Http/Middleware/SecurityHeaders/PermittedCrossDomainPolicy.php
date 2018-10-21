<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Middleware\SecurityHeaders;

/**
 * X-Permitted-Cross-Domain-Policy Header Values
 */
interface PermittedCrossDomainPolicy
{
    /** @var string X-Permitted-Cross-Domain-Policy all */
    const ALL = 'all';

    /** @var string X-Permitted-Cross-Domain-Policy none */
    const NONE = 'none';

    /** @var string X-Permitted-Cross-Domain-Policy master-only */
    const MASTER_ONLY = 'master-only';

    /** @var string X-Permitted-Cross-Domain-Policy by-content-type */
    const BY_CONTENT_TYPE = 'by-content-type';

    /** @var string X-Permitted-Cross-Domain-Policy by-ftp-filename */
    const BY_FTP_FILENAME = 'by-ftp-filename';
}
