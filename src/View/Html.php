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
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link https://cakephp.org CakePHP(tm) Project
 * @since 6.0.0
 * @license https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

use JsonSerializable;
use function Cake\Core\h;

/**
 * An immutable value containing developer-controlled HTML.
 *
 * This class marks markup as trusted; it does not sanitize it.
 */
final readonly class Html implements HtmlStringable, JsonSerializable
{
    /**
     * Wrap trusted markup without escaping it.
     *
     * @param string $value The trusted HTML.
     */
    public function __construct(public string $value)
    {
    }

    /**
     * Return the original markup.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Escape ordinary text while preserving explicitly trusted HTML content.
     *
     * @param \Cake\View\HtmlStringable|string $text Text or trusted markup to render.
     * @return string
     */
    public static function text(string|HtmlStringable $text): string
    {
        return $text instanceof HtmlStringable ? (string)$text : h($text);
    }

    /**
     * Serialize the markup as a JSON string.
     *
     * @return string
     */
    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
