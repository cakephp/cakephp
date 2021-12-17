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
 * @since         4.4.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace TestApp\View;

use Cake\View\View;

/**
 * CustomJsonView class
 */
class PlainTextView extends View
{
    /**
     * @inheritDoc
     */
    public static function contentType(): string
    {
        return 'text/plain';
    }

    /**
     * @inheritDoc
     */
    public function render(?string $template = null, $layout = null): string
    {
        return $this->get('body') ?? '';
    }
}
