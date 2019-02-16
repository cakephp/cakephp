<?php
declare(strict_types=1);
namespace TestApp\Renderer;

use Cake\Mailer\Renderer;

class TestRenderer extends Renderer
{
    /**
     * Wrap to protected method
     *
     * @param string $text
     * @param int $length
     * @return array
     */
    public function doWrap($text, $length = Renderer::LINE_LENGTH_MUST)
    {
        return $this->wrap($text, $length);
    }
}
