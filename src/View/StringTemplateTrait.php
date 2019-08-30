<?php
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
 * @since         3.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\View;

/**
 * Adds string template functionality to any class by providing methods to
 * load and parse string templates.
 *
 * This trait requires the implementing class to provide a `config()`
 * method for reading/updating templates. An implementation of this method
 * is provided by `Cake\Core\InstanceConfigTrait`
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
     * Sets templates to use.
     *
     * @param string[] $templates Templates to be added.
     * @return $this
     */
    public function setTemplates(array $templates)
    {
        $this->templater()->add($templates);

        return $this;
    }

    /**
     * Gets templates to use or a specific template.
     *
     * @param string|null $template String for reading a specific template, null for all.
     * @return string|array
     */
    public function getTemplates($template = null)
    {
        return $this->templater()->get($template);
    }

    /**
     * Gets/sets templates to use.
     *
     * @deprecated 3.4.0 Use setTemplates()/getTemplates() instead.
     * @param string|null|array $templates null or string allow reading templates. An array
     *   allows templates to be added.
     * @return $this|string|array
     */
    public function templates($templates = null)
    {
        deprecationWarning(
            'StringTemplateTrait::templates() is deprecated. ' .
            'Use setTemplates()/getTemplates() instead.'
        );

        if ($templates === null || is_string($templates)) {
            return $this->templater()->get($templates);
        }

        $this->templater()->add($templates);

        return $this;
    }

    /**
     * Formats a template string with $data
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
     * Returns the templater instance.
     *
     * @return \Cake\View\StringTemplate
     */
    public function templater()
    {
        if ($this->_templater === null) {
            $class = $this->getConfig('templateClass') ?: 'Cake\View\StringTemplate';
            $this->_templater = new $class();

            $templates = $this->getConfig('templates');
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
