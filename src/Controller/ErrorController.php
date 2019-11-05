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
 * @since         2.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Controller;

use Cake\Event\EventInterface;

/**
 * Error Handling Controller
 *
 * Controller used by ErrorHandler to render error views.
 */
class ErrorController extends Controller
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->loadComponent('RequestHandler');
    }

    /**
     * beforeRender callback.
     *
     * @param \Cake\Event\EventInterface $event Event.
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        $builder = $this->viewBuilder();
        $templatePath = 'Error';

        if (
            $this->request->getParam('prefix') &&
            in_array($builder->getTemplate(), ['error400', 'error500'], true)
        ) {
            $parts = explode(DIRECTORY_SEPARATOR, (string)$builder->getTemplatePath(), -1);
            $templatePath = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . 'Error';
        }

        $builder->setTemplatePath($templatePath);
    }
}
