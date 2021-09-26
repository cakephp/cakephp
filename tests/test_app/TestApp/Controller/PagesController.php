<?php
declare(strict_types=1);

/**
 * Static content controller.
 *
 * This file will render views from views/pages/
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         0.2.9
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingTemplateException;

/**
 * Static content controller
 */
class PagesController extends AppController
{
    /**
     * Displays a view
     *
     * @param mixed What page to display
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When the view file could not be found.
     * @throws \Cake\View\Exception\MissingTemplateException In debug mode.
     */
    public function display()
    {
        $path = func_get_args();

        $count = count($path);
        if (!$count) {
            return $this->redirect('/');
        }
        $page = $subpage = $titleForLayout = null;

        if (!empty($path[0])) {
            $page = $path[0];
        }
        if (!empty($path[1])) {
            $subpage = $path[1];
        }
        if (!empty($path[$count - 1])) {
            $titleForLayout = Inflector::humanize($path[$count - 1]);
        }
        $this->set([
            'page' => $page,
            'subpage' => $subpage,
            'title_for_layout' => $titleForLayout,
        ]);

        try {
            $this->render(implode('/', $path));
        } catch (MissingTemplateException $e) {
            if (Configure::read('debug')) {
                throw $e;
            }
            throw new NotFoundException();
        }
    }
}
