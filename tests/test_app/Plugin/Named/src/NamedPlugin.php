<?php
declare(strict_types=1);

namespace Named;

use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;

class NamedPlugin extends BasePlugin
{
    public function bootstrap(PluginApplicationInterface $app): void
    {
        Configure::write('Named.bootstrap', true);

        $app->addPlugin('TestPluginTwo', ['bootstrap' => true]);
        $app->addPlugin('TestPlugin', ['bootstrap' => true]);
    }
}
