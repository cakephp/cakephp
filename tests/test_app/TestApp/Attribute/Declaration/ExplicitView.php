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

use TestApp\View\Helper\TestBeforeAfterHelper;

/**
 * Overrides an inherited helper declaration during view initialization.
 */
class ExplicitView extends ArticlesView
{
    /**
     * Supplies explicit helper configuration before declarations are applied.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->addHelper('Observer', ['className' => TestBeforeAfterHelper::class, 'flag' => 'explicit']);
    }
}
