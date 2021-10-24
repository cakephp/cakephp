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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View\Widget;

use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetInterface;

class TestUsingViewWidget implements WidgetInterface
{
    protected $_templates;

    protected $_view;

    public function __construct(StringTemplate $templates, View $view)
    {
        $this->_templates = $templates;
        $this->_view = $view;
    }

    public function getView(): View
    {
        return $this->_view;
    }

    /**
     * @inheritDoc
     */
    public function render(array $data, ContextInterface $context): string
    {
        return '<success></success>';
    }

    /**
     * @inheritDoc
     */
    public function secureFields(array $data): array
    {
        if (!isset($data['name']) || $data['name'] === '') {
            return [];
        }

        return [$data['name']];
    }
}
