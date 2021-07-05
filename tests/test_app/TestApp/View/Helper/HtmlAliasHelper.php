<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\Event\EventInterface;
use Cake\View\Helper;

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends Helper
{
    public function afterRender(EventInterface $event, string $viewFile): void
    {
    }
}
