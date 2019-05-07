<?php
declare(strict_types=1);

namespace TestApp\View\Helper;

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
     * @return void
     */
    public function beforeLayout($viewFile)
    {
        $this->property = 'Valuation';
    }

    /**
     * afterLayout method
     *
     * @param string $layoutFile Layout file name.
     * @return void
     */
    public function afterLayout($layoutFile)
    {
        $this->_View->append('content', 'modified in the afterlife');
    }
}
