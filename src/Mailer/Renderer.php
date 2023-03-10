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
 * @since         4.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Mailer;

use Cake\View\View;
use Cake\View\ViewVarsTrait;
use function Cake\Core\pluginSplit;

/**
 * Class for rendering email message.
 */
class Renderer
{
    use ViewVarsTrait;

    /**
     * Constant for folder name containing email templates.
     *
     * @var string
     */
    public const TEMPLATE_FOLDER = 'email';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Render text/HTML content.
     *
     * If there is no template set, the $content will be returned in a hash
     * of the specified content types for the email.
     *
     * @param string $content The content.
     * @param array<string> $types Content types to render. Valid array values are Message::MESSAGE_HTML, Message::MESSAGE_TEXT.
     * @return array<string, string> The rendered content with "html" and/or "text" keys.
     * @psalm-param array<\Cake\Mailer\Message::MESSAGE_HTML|\Cake\Mailer\Message::MESSAGE_TEXT> $types
     * @psalm-return array{html?: string, text?: string}
     */
    public function render(string $content, array $types = []): array
    {
        $rendered = [];
        $template = $this->viewBuilder()->getTemplate();
        if (empty($template)) {
            foreach ($types as $type) {
                $rendered[$type] = $content;
            }

            return $rendered;
        }

        $view = $this->createView();

        [$templatePlugin] = pluginSplit($view->getTemplate());
        [$layoutPlugin] = pluginSplit($view->getLayout());
        if ($templatePlugin) {
            $view->setPlugin($templatePlugin);
        } elseif ($layoutPlugin) {
            $view->setPlugin($layoutPlugin);
        }

        if ($view->get('content') === null) {
            $view->set('content', $content);
        }

        foreach ($types as $type) {
            $view->setTemplatePath(static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $type);
            $view->setLayoutPath(static::TEMPLATE_FOLDER . DIRECTORY_SEPARATOR . $type);

            $rendered[$type] = $view->render();
        }

        return $rendered;
    }

    /**
     * Reset view builder to defaults.
     *
     * @return $this
     */
    public function reset()
    {
        $this->_viewBuilder = null;

        $this->viewBuilder()
            ->setClassName(View::class)
            ->setLayout('default')
            ->setHelpers(['Html'], false);

        return $this;
    }

    /**
     * Clone ViewBuilder instance when renderer is cloned.
     *
     * @return void
     */
    public function __clone()
    {
        if ($this->_viewBuilder !== null) {
            $this->_viewBuilder = clone $this->_viewBuilder;
        }
    }
}
