<?php
declare(strict_types=1);
namespace TestApp\View;

class TestView extends AppView
{
    public function initialize(): void
    {
        $this->loadHelper('Html', ['mykey' => 'myval']);
    }

    /**
     * getViewFileName method
     *
     * @param string $name Controller action to find template filename for
     * @return string Template filename
     */
    public function getViewFileName($name = null)
    {
        return $this->_getViewFileName($name);
    }

    /**
     * getLayoutFileName method
     *
     * @param string $name The name of the layout to find.
     * @return string Filename for layout file (.php).
     */
    public function getLayoutFileName($name = null)
    {
        return $this->_getLayoutFileName($name);
    }

    /**
     * paths method
     *
     * @param string $plugin Optional plugin name to scan for view files.
     * @param bool $cached Set to true to force a refresh of view paths.
     * @return array paths
     */
    public function paths($plugin = null, $cached = true)
    {
        return $this->_paths($plugin, $cached);
    }

    /**
     * Setter for extension.
     *
     * @param string $ext The extension
     * @return void
     */
    public function ext($ext)
    {
        $this->_ext = $ext;
    }
}
