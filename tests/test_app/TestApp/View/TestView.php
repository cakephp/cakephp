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
     * getTemplateFileName method
     *
     * @param string|null $name Controller action to find template filename for
     * @return string Template filename
     */
    public function getTemplateFileName(?string $name = null): string
    {
        return $this->_getTemplateFileName($name);
    }

    /**
     * getLayoutFileName method
     *
     * @param string|null $name The name of the layout to find.
     * @return string Filename for layout file (.php).
     */
    public function getLayoutFileName(?string $name = null): string
    {
        return $this->_getLayoutFileName($name);
    }

    /**
     * paths method
     *
     * @param string|null $plugin Optional plugin name to scan for view files.
     * @param bool $cached Set to true to force a refresh of view paths.
     * @return string[] paths
     */
    public function paths(?string $plugin = null, bool $cached = true): array
    {
        return $this->_paths($plugin, $cached);
    }

    /**
     * Setter for extension.
     *
     * @param string $ext The extension
     */
    public function ext(string $ext): void
    {
        $this->_ext = $ext;
    }
}
