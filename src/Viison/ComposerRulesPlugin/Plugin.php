<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface {

    public function activate(Composer $composer, IOInterface $io)
    {
        $logger = new Logger($io);
        $installer = new Installer($io, $composer, null, $logger);
        $composer->getInstallationManager()->disablePlugins();
        $composer->getInstallationManager()->addInstaller($installer);
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onCommand', 0)
            ),
        );
    }

    public function onCommand(\Composer\Plugin\CommandEvent $event)
    {
        echo __METHOD__, ' ', $event->getCommandName(), " ################ \n";
    }

}
