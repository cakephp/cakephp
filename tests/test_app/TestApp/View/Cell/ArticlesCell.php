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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View\Cell;

use Cake\View\Cell;

/**
 * TagCloudCell class
 */
class ArticlesCell extends Cell
{
    /**
     * valid cell options.
     *
     * @var array<string>
     */
    protected $_validCellOptions = ['limit', 'page'];

    /**
     * Counter used to test the cache cell feature
     *
     * @var int
     */
    public $counter = 0;

    /**
     * Default cell action.
     */
    public function display(): void
    {
    }

    /**
     * Renders articles in teaser view mode.
     */
    public function teaserList(): void
    {
        $this->set('articles', [
            ['title' => 'Lorem ipsum', 'body' => 'dolorem sit amet'],
            ['title' => 'Usectetur adipiscing eli', 'body' => 'tortor, in tincidunt sem dictum vel'],
            ['title' => 'Topis semper blandit eu non', 'body' => 'alvinar diam convallis non. Nullam pu'],
            ['title' => 'Suspendisse gravida neque', 'body' => 'pellentesque sed scelerisque libero'],
        ]);
    }

    /**
     * Renders a view using a different template than the action name
     * The template is set using the ViewBuilder bound to the Cell
     */
    public function customTemplateViewBuilder(): void
    {
        $this->counter++;
        $this->viewBuilder()->setTemplate('alternate_teaser_list');
    }

    /**
     * Renders a template in a custom templatePath
     * The template is set using the ViewBuilder bound to the Cell
     */
    public function customTemplatePath(): void
    {
        $this->viewBuilder()->setTemplatePath(static::TEMPLATE_FOLDER . '/Articles/Subdir');
    }

    /**
     * Simple echo.
     */
    public function doEcho(string $msg1, string $msg2): void
    {
        $this->set('msg', $msg1 . $msg2);
    }
}
