<?php
namespace ParentPlugin;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin as CorePlugin;
use Cake\Core\PluginApplicationInterface;

class Plugin extends BasePlugin
{
    public function bootstrap(PluginApplicationInterface $app)
    {
        Configure::write('ParentPlugin.bootstrap', true);

        CorePlugin::load('TestPluginTwo', ['bootstrap' => true]);
        $app->addPlugin('TestPlugin', ['bootstrap' => true]);
    }
}
