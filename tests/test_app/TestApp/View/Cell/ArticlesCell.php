<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View\Cell;

/**
 * TagCloudCell class
 *
 */
class ArticlesCell extends \Cake\View\Cell
{

    /**
     * valid cell options.
     *
     * @var array
     */
    protected $_validCellOptions = ['limit', 'page'];

    /**
     * Default cell action.
     *
     * @return void
     */
    public function display()
    {
    }

    /**
     * Renders articles in teaser view mode.
     *
     * @return void
     */
    public function teaserList()
    {
        $this->set('articles', [
            ['title' => 'Lorem ipsum', 'body' => 'dolorem sit amet'],
            ['title' => 'Usectetur adipiscing eli', 'body' => 'tortor, in tincidunt sem dictum vel'],
            ['title' => 'Topis semper blandit eu non', 'body' => 'alvinar diam convallis non. Nullam pu'],
            ['title' => 'Suspendisse gravida neque', 'body' => 'pellentesque sed scelerisque libero'],
        ]);
    }

    /**
     * Renders articles in teaser view mode.
     *
     * @return void
     */
    public function customTemplate()
    {
        $this->template = 'alternate_teaser_list';
        $this->set('articles', [
            ['title' => 'Lorem ipsum', 'body' => 'dolorem sit amet'],
            ['title' => 'Usectetur adipiscing eli', 'body' => 'tortor, in tincidunt sem dictum vel'],
            ['title' => 'Topis semper blandit eu non', 'body' => 'alvinar diam convallis non. Nullam pu'],
            ['title' => 'Suspendisse gravida neque', 'body' => 'pellentesque sed scelerisque libero'],
        ]);
    }

    /**
     * Simple echo.
     *
     * @param string $msg1
     * @param string $msg2
     * @return void
     */
    public function doEcho($msg1, $msg2)
    {
        $this->set('msg', $msg1 . $msg2);
    }
}
