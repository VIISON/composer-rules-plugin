VIISON/composer-rules-plugin
============================

This free and open source MIT-licensed Composer plugin allows you to add
additional processing rules to your Composer-based dependency installation.

Usage:
------

1. This plugin is not published on packagist yet. To add it to your
   Composer-based project, add the corresponding repository for it to your
   `composer.json`. Composer's documentation describes how to [work with
   repositories](https://getcomposer.org/doc/05-repositories.md#vcs).

2. Add the plugin to your `composer.json` as a dependency:

    ```javascript
        ...
        "require": {
            ...
            "VIISON/composer-rules-plugin": "dev-master",
            ...
        },
        ...
    ```

3. Given this plugin has not officially been released yet, you have to set the
   minimum (as in minimum required) stability of your dependencies to `dev`
   in your `composer.json`:

    ```javascript
        "minimum-stability": "dev",
    ```

4. Add any rules you want to your `composer.json`:

    ```javascript
        ...
        "extra": {
            "composer-rules-plugin": {
                "rules": [
                    {
                        "rule": "rule-add-installer",
                        "class": "Composer\\Installers\\Installer"
                    },
                    {
                        "rule": "rule-symlink-deps-of-deps",
                        "match-outer-deps": ["dep/a"],
                        "match-inner-deps": ["dep/b"],
                        "symlink-dest": ["%outerdir%/symlink-to-b-inside-a"]
                    }
                ]
            }
        }
        ...
    ```

Supported rules
---------------

rule-add-installer
:   Adds another [composer installer plugin
    class](https://getcomposer.org/doc/articles/custom-installers.md).
    The installer class is determined by the `class` parameter.
    This class must implement Composer's `InstallerInterface` and must
    have a public constructor supporting with the following signature:

    ```php
    <?php
    use Composer\Composer;
    use Composer\Installer\InstallerInterface;
    use Composer\IO\IOInterface;
    use Composer\Util\Filesystem;
    ...
    class MyInstaller implements InstallerInterface {
        public function __construct(
            IOInterface $io,
            Composer $composer,
            $type = 'library',
            Filesystem $filesystem = null) { /* ... */ }
    }
    ```

    This rule is intended to allow concurrent usage of the
    [composer/installers](https://composer.github.com/installers) plugin with
    this plugin. The following example shows this use case:

    ```javascript
    {
        ...
        "require:" {
            ...
            "VIISON/composer-rules-plugin": "dev-master",
            ...
        },
        ...
        "extra": {
            "composer-rules-plugin": {
                "rules": [
                    {
                        "rule": "rule-add-installer",
                        "class": "Composer\\Installers\\Installer"
                    }
                ]
            }
        }
    }
    ```

rule-symlink-deps-of-deps
:   Creates symbolic links to an indirect dependency in the directory of
    an outer dependency:

    ```javascript
    {
        ...
        "require:" {
            ...
            "dep/a": "*",
            "VIISON/composer-rules-plugin": "dev-master",
            ...
        },
        ...
        "extra": {
            "composer-rules-plugin": {
                "rules": [
                    {
                        "rule": "rule-symlink-deps-of-deps",
                        "match-outer-deps": ["dep/a"],
                        "match-inner-deps": ["dep/b"],
                        "symlink-dest": ["%outerdir%/symlink-to-b-inside-a"]
                    }
                ]
            }
        }
    }
    ```

    In the above example, `dep/a` is a direct dependency of the root project.
    `dep/b` is a dependency of `dep/a`. The `symlink-dest` parameters defines
    that in the directory of the _outer_ dependency (`%outerdir%`), an
    absolute symbolic link will be created named `symlink-to-b-inside-a`, with
    the location of `dep/b` as its target.

Supported hooks
---------------

Rules must implement the [Rule
interface](src/Viison/ComposerRulesPlugin/Rule.php) and can then
manipulate dependencies' installation paths and execute actions after a
dependency has been installed.

The list of supported rules is currently statically configured. This may
change in future versions.

Caveats
-------

* At this time, only the `composer install` use case is supported.
  `composer update` and others may be broken by usage of this plugin.

License
-------

`VIISON/composer-rules-plugin` is licensed under the MIT License - see the
LICENSE file for details.
