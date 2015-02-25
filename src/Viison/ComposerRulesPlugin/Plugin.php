<?php
/**
 * This file is part of VIISON/composer-rules-plugin.
 *
 * Copyright (c) 2014-2015 VIISON GmbH
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * @license MIT <http://opensource.org/licenses/MIT>
 */

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

    /**
     * FIXME: Need to evaluate how conflicts with other installer plugins may
     *     arise.
     */
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
        $this->installer->runRemainingPostInstalls();
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
