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

use Cake\View\ViewVarsTrait;

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
     * Build and set all the view properties needed to render the templated emails.
     * If there is no template set, the $content will be returned in a hash
     * of the text content types for the email.
     *
     * @param string $content The content.
     * @param array $types Content types to render.
     * @return array The rendered content with "html" and/or "text" keys.
     */
    public function getContent(string $content, array $types = []): array
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
        [$layoutPlugin] = pluginSplit((string)$view->getLayout());
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

            $rendered[$type] = (string)$view->render();
        }

        return $rendered;
    }
}
