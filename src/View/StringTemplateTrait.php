<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

/**
 * Adds string template functionality to any class by providing methods to
 * load and parse string templates.
 */
trait StringTemplateTrait
{

    /**
     * StringTemplate instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected $_templater;

    /**
     * Gets/Sets templates to use.
     *
     * @param string|null|array $templates null or string allow reading templates. An array
     *   allows templates to be added.
     * @return $this|string|array
     */
    public function templates($templates = null)
    {
        if ($templates === null || is_string($templates)) {
            return $this->templater()->get($templates);
        }

        $this->templater()->add($templates);
        return $this;
    }

    /**
     * Format a template string with $data
     *
     * @param string $name The template name.
     * @param array $data The data to insert.
     * @return string
     */
    public function formatTemplate($name, $data)
    {
        return $this->templater()->format($name, $data);
    }

    /**
     * templater
     *
     * @return \Cake\View\StringTemplate
     */
    public function templater()
    {
        if (empty($this->_templater)) {
            $class = $this->config('templateClass') ?: 'Cake\View\StringTemplate';
            $this->_templater = new $class();

            $templates = $this->config('templates');
            if ($templates) {
                if (is_string($templates)) {
                    $this->_templater->add($this->_defaultConfig['templates']);
                    $this->_templater->load($templates);
                } else {
                    $this->_templater->add($templates);
                }
            }
        }
        return $this->_templater;
    }
}
