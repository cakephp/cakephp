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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\View\JsonView;
use Cake\View\XmlView;


/**
 * ContentTypesController class
 */
class ContentTypesController extends AppController
{
    /**
     * @var array<string>
     */
    protected $viewClasses = [];

    public function getViewClasses(): array
    {
        return $this->viewClasses;
    }

    public function all()
    {
        $this->viewClasses = [JsonView::class, XmlView::class];
        $this->set('data', ['hello', 'world']);
        $this->viewBuilder()->setOption('serialize', ['data']);
    }
}
