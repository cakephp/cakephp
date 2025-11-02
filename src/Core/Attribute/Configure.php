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
 * @since         5.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Core\Attribute;

use Attribute;
use Cake\Core\Configure as CakeConfigure;
use League\Container\Attribute\AttributeInterface;

/**
 * Configure attribute for dependency injection container delegate.
 *
 * This provides autowiring config data into constructors when delegate is enabled.
 *
 * Example:
 * ```
 * <?php
 * declare(strict_types=1);
 *
 * namespace App\Model\WebService;
 * use Cake\Core\Attribute\Configure;
 *
 * class CustomClient
 * {
 *     public function __construct(
 *         #[Configure('CustomService.apiKey')] protected string $apiKey,
 *     ) { }
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Configure implements AttributeInterface
{
    /**
     * @param string $name
     */
    public function __construct(private string $name)
    {
    }

    /**
     * @return mixed
     */
    public function resolve(): mixed
    {
        return CakeConfigure::read($this->name);
    }
}
