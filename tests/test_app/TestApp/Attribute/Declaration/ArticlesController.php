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
namespace TestApp\Attribute\Declaration;

use Cake\Controller\Attribute\UseComponent;
use Cake\Controller\Controller;
use stdClass;
use TestApp\Controller\Component\AppleComponent;

/**
 * Declares a component using a custom alias and construction options.
 */
#[UseComponent(AppleComponent::class, alias: 'Observer', config: ['flag' => 'declared', 'state' => new stdClass()])]
class ArticlesController extends Controller
{
    /**
     * Whether the component was unexpectedly available during initialization.
     *
     * @var bool
     */
    public bool $componentDuringInitialize = false;

    /**
     * Captures registry state before attribute declarations are loaded.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->componentDuringInitialize = $this->components()->has('Observer');
    }
}
