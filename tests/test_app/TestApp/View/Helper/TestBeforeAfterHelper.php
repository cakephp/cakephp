<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

use Cake\Event\EventInterface;
use Cake\View\Helper;

class TestBeforeAfterHelper extends Helper
{
    /**
     * property property
     *
     * @var string
     */
    public $property = '';

    /**
     * beforeLayout method
     *
     * @param string $viewFile View file name.
     */
    public function beforeLayout(EventInterface $event, string $viewFile): void
    {
        $this->property = 'Valuation';
    }

    /**
     * afterLayout method
     *
     * @param string $layoutFile Layout file name.
     */
    public function afterLayout(EventInterface $event, string $layoutFile): void
    {
        $this->_View->append('content', 'modified in the afterlife');
    }
}
