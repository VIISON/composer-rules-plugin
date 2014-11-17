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
        $installer = new Installer($io, $composer);
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
