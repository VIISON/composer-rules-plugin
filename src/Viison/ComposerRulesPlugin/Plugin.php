<?php

namespace Viison\ComposerRulesPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\PluginEvents;
use Composer\Script\ScriptEvents;
use Composer\Script\Event;
use Composer\EventDispatcher\EventSubscriberInterface;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Installer
     */
    protected $installer;

    /**
     * @var Logger
     */
    protected $logger;

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->logger = new Logger($io);
        $this->installer = new Installer($io, $composer, null, $this->logger);
        //$composer->getInstallationManager()->disablePlugins();
        $composer->getInstallationManager()->addInstaller($this->installer);
    }

    public static function getSubscribedEvents()
    {
        return array(
            PluginEvents::COMMAND => array(
                array('onCommand', 0),
            ),
            ScriptEvents::POST_INSTALL_CMD => 'onPostInstallCmd',
            ScriptEvents::POST_ROOT_PACKAGE_INSTALL => 'onPostRootPackageInstall',
            ScriptEvents::POST_PACKAGE_INSTALL => 'onPostPackageInstall',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCmd',
        );
    }

    public function onCommand(\Composer\Plugin\CommandEvent $event)
    {
    }

    protected function handleScriptEvent(Event $event)
    {
        $this->logger->logMethod(__METHOD__,
            array('event: ', $event->getName()));
    }

    public function onPostInstallCmd(Event $event)
    {
        $this->installer->runRemainingPostInstalls();
    }

    public function onPostUpdateCmd(Event $event)
    {
        $this->handleScriptEvent($event);
    }

    public function onPostPackageInstall(Event $event)
    {
        $this->handleScriptEvent($event);
    }

    public function onPostRootPackageInstall(Event $event)
    {
        $this->handleScriptEvent($event);
    }
}
