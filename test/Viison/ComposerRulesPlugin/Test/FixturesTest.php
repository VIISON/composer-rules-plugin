<?php

namespace Viison\ComposerRulesPlugin\Test;

class FixturesTest
    extends \PHPUnit_Framework_TestCase
{
    const COMPOSER_JSON = 'composer.json';
    const COMPOSER_JSON_IN = 'composer.json.in';
    const COMPOSER_JSON_POST_UPDATE_IN = 'composer-post-update.json.in';

    protected $cleanFiles = null;
    protected $cleanDirs = null;

    /**
     * @before
     */
    public function setup()
    {
        $this->cleanFiles = array();
        $this->cleanDirs = array();
        $this->packageZips = array();
    }

    /**
     * @after
     */
    public function teardown()
    {
        foreach ($this->cleanFiles as $cleanFile) {
            if (file_exists($cleanFile) && unlink($cleanFile) === false) {
                throw new \Exception('File '.escapeshellarg($cleanFile)
                    .' could not be removed on clean-up.');
            }
        }

        foreach ($this->cleanDirs as $cleanDir) {
            $output = array();
            $returnVal = -1;
            $this->assertNotEquals('', $cleanDir);
            $this->assertNotEquals('/', $cleanDir);
            $this->assertNotEquals('.', $cleanDir);
            $cmd = 'rm -rf --preserve-root -- '.escapeshellarg($cleanDir);
            exec($cmd, $output, $returnVal);
            $this->assertEquals(0, $returnVal,
                'Could not run command '.$cmd.'. Output was: '.PHP_EOL
                .implode(PHP_EOL, $output)
                );
        }

        $this->packageZips = array();
    }

    /**
     * Constructs a list of fixtures from
     * `test/Viison/ComposerRulesPlugin/Test/data/*`.
     *
     * @return array An array of arrays, each containing the name of the
     *               fixture (based on its directory), its directory and the
     *               packages within the fixture.
     */
    public function testFixturesProvider()
    {
        $dataDir = dirname(__FILE__).DIRECTORY_SEPARATOR.'data';

        $testRuns = array();

        $fixtureDirs = scandir($dataDir);
        if ($fixtureDirs === false) {
            throw new \Exception('Reading directory '
                .escapeshellarg($dataDir).'failed.');
        }
        foreach ($fixtureDirs as $fixtureDir) {
            if ($fixtureDir === '.' || $fixtureDir === '..') {
                continue;
            }
            $fixtureDir = $dataDir.DIRECTORY_SEPARATOR.$fixtureDir;
            $realFixtureDir = realpath($fixtureDir);
            if ($realFixtureDir === false) {
                throw new \Exception('Realpath for '
                    .escapeshellarg($fixtureDir).' could not be determined');
            }
            $fixtureDir = $realFixtureDir;
            if (!is_dir($fixtureDir)) {
                continue;
            }
            $fixtureName = basename($fixtureDir);
            $packageDirs = scandir($fixtureDir);
            $packages = array();
            foreach ($packageDirs as $packageDir) {
                if ($packageDir === '.' || $packageDir === '..') {
                    continue;
                }
                $packageDir = $fixtureDir.DIRECTORY_SEPARATOR.$packageDir;
                $realPackageDir = realpath($packageDir);
                if ($realPackageDir === false) {
                    throw new \Exception('Realpath for '
                        .escapeshellarg($packageDir)
                        .' could not be determined');
                }
                $packageDir = $realPackageDir;
                if (!is_dir($packageDir)) {
                    continue;
                }
                $composerIn = $this->readComposerJsonIn($packageDir);
                $packageName = $composerIn->name;
                $packages[$packageName] = $packageDir;
            }
            $testRuns[] = array(
                $fixtureName,
                $fixtureDir,
                $packages,
            );
        }

        return $testRuns;
    }

    protected function readComposerJsonIn($packageDir)
    {
        return $this->readJson($packageDir.DIRECTORY_SEPARATOR
            .self::COMPOSER_JSON_IN);
    }

    protected function readJson($fileName)
    {
        $contents = file_get_contents($fileName);
        if ($contents === false) {
            throw new \Exception('Reading file '
            .escapeshellarg($fileName)
            .' failed.');
        }
        $jsonObj = json_decode($contents);
        if (is_null($jsonObj)) {
            throw new \Exception('No valid JSON in '
                .escapeshellarg($fileName).': '
                .json_last_error_msg());
        }

        return $jsonObj;
    }

    const COMPOSER_RULES_PLUGIN_PKG_NAME = 'Viison/composer-rules-plugin';

    protected function prepareFixture($fixtureName, $fixtureDir, array
        $packages)
    {
        $this->fixtureVariables = array_map(function ($pkgName) {
            return '@'.$pkgName.'-zip@';
        }, array_keys($packages));

        $this->fixtureReplacements = array();
        foreach ($packages as $packageName => $packageDir) {
            $this->fixtureReplacements[] = 'file://'
                .$this->getPackageZipFileName($packageName, $packageDir);
        }

        $this->fixtureVariables[] = '@'.self::COMPOSER_RULES_PLUGIN_PKG_NAME
            .'-git@';
        $this->fixtureReplacements[] = getcwd();

        foreach ($packages as $packageName => $packageDir) {
            $this->prepareFixturePackage($packageName, $packageDir);
        }
    }

    const ZIP_BINARY = 'zip';
    const COMPOSER_BINARY = 'composer';

    protected $packageZips = null;

    protected function getPackageZipFileName($packageName, $packageDir)
    {
        $packageName = strtolower($packageName);
        if (isset($this->packageZips[$packageName])) {
            return $this->packageZips[$packageName];
        }
        $fileName = tempnam(sys_get_temp_dir(), basename($packageDir).'-');
        $zipName = $fileName.'.zip';
        if (!rename($fileName, $zipName)) {
            unlink($fileName);
            throw new \Exception('Temporary file '.escapeshellarg($zipName)
                .' could not be created.');
        }

        if (!in_array($zipName, $this->cleanFiles)) {
            $this->cleanFiles[] = $zipName;
        }

        $this->packageZips[$packageName] = $zipName;

        return $zipName;
    }

    protected function replaceComposerJson($inputFileName,
        $packageDir)
    {
        $composerIn = $this->readJson($packageDir
            .DIRECTORY_SEPARATOR.$inputFileName);
        $composerJsonFileName = $packageDir.DIRECTORY_SEPARATOR
                .self::COMPOSER_JSON;
        if (file_exists($composerJsonFileName)) {
            if (unlink($composerJsonFileName) === false) {
                throw new \Exception('File '
                    .escapeshellarg($composerJsonFileName)
                    .' could not be removed.');
            }
        }

        $composer = $this->replaceDeepJsonValue(
            $composerIn, $this->fixtureVariables, $this->fixtureReplacements);
        file_put_contents($composerJsonFileName,
            json_encode($composer,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->cleanFiles[] = $composerJsonFileName;
    }

    protected function prepareFixturePackage($packageName, $packageDir)
    {
        if ($packageName !== self::COMPOSER_RULES_PLUGIN_PKG_NAME) {
            // ^ Do not manipulate the plugin's proper composer.json.
            $this->replaceComposerJson(self::COMPOSER_JSON_IN, $packageDir);
        }

        $packageZip = $this->getPackageZipFileName($packageName, $packageDir);

        $currentDir = getcwd();
        try {
            $packageTopDir = dirname($packageDir);
            $packageDirName = basename($packageDir);
            $returnVal = -1;
            $output = array();
            $zipCmd = escapeshellcmd(self::ZIP_BINARY)
                .' -r '
                .escapeshellarg($packageZip)
                .' '
                .escapeshellarg($packageDirName)
                .' --exclude \*/.git/\* \*/vendor/\*';
            if (!chdir($packageTopDir)) {
                throw new \Exception('Could not chdir to '.$packageTopDir);
            }
            if (file_exists($packageZip) && !unlink($packageZip)) {
                throw new \Exception(escapeshellarg($packageZip)
                    .' already exists and could not be removed.');
            }
            exec($zipCmd,
                $output,
                $returnVal);

            $this->assertEquals(0, $returnVal,
                'Could not run command '.$zipCmd.'. Output was: '.PHP_EOL
                .implode(PHP_EOL, $output));
        } finally {
            if (!chdir($currentDir)) {
                throw new \Exception('Could not chdir back to '
                    .escapeshellarg($currentDir).'.');
            }
        }
    }

    /**
     * Analogous to str_replace but applies to all values of `$json`'s object
     * hierarchy.
     */
    protected function replaceDeepJsonValue($json, $pattern, $replacement)
    {
        if (is_object($json) || is_array($json)) {
            foreach ($json as $key => $value) {
                $newVal = $this->replaceDeepJsonValue(
                    $value, $pattern, $replacement);
                if (is_array($json)) {
                    $json[$key] = $newVal;
                } else {
                    $json->{$key} = $newVal;
                }
            }

            return $json;
        }
        if (is_scalar($json)) {
            return str_replace($pattern, $replacement, $json);
        }

        return $json;
    }

    /**
     * @dataProvider testFixturesProvider
     */
    public function testFixture($fixtureName, $fixtureDir, array $packages)
    {
        $this->prepareFixture($fixtureName, $fixtureDir, $packages);
        $rootPkgDir = false;
        foreach ($packages as $pkgName => $pkgDir) {
            if (preg_match(',root$,u', $pkgDir) === 1) {
                $rootPkgDir = $pkgDir;
            }
        }
        if (empty($rootPkgDir)) {
            throw new \Exception('No root package found for fixture.');
        }
        $this->composerInstall($rootPkgDir, $fixtureName);

        $updateJsonIn = $rootPkgDir.DIRECTORY_SEPARATOR
            .self::COMPOSER_JSON_POST_UPDATE_IN;
        if (file_exists($updateJsonIn)) {
            $this->replaceComposerJson(self::COMPOSER_JSON_POST_UPDATE_IN,
                $rootPkgDir);
            $this->composerUpdate($rootPkgDir, $fixtureName);
        }
    }

    protected function composerInstall($rootPkgDir, $fixtureName)
    {
        $this->runComposer($rootPkgDir, array('install', '-v'));

        $postComposerInstallTestMethodName = 'postComposerInstall'
            .implode('', array_map('ucfirst', explode('-', $fixtureName)));
        $this->{$postComposerInstallTestMethodName}($rootPkgDir);
    }

    protected function composerUpdate($rootPkgDir, $fixtureName)
    {
        $this->runComposer($rootPkgDir, array('update', '-v'));

        $postComposerUpdateTestMethodName = 'postComposerUpdate'
            .implode('', array_map('ucfirst', explode('-', $fixtureName)));
        $this->{$postComposerUpdateTestMethodName}($rootPkgDir);
    }

    protected function runComposer($rootPkgDir, array $args)
    {
        $currentDir = realpath(getcwd());
        try {
            chdir($rootPkgDir);
            $composerOutput = array();
            $composerReturnVal = -1;
            $output = array();
            $composerCmd = escapeshellcmd(self::COMPOSER_BINARY)
                .' '
                .implode(' ', array_map('escapeshellarg', $args))
                .' 2>&1';

            $this->cleanFiles[] = $rootPkgDir.DIRECTORY_SEPARATOR
                .'composer.lock';
            $this->cleanDirs[] = $rootPkgDir.DIRECTORY_SEPARATOR
                .'vendor';

            exec($composerCmd,
                $composerOutput,
                $composerReturnVal);

            $composerLog = $rootPkgDir.DIRECTORY_SEPARATOR.'composer.log';
            $this->cleanFiles[] = $composerLog;
            file_put_contents($composerLog,
                implode(PHP_EOL, $composerOutput));

            $this->assertEquals(0, $composerReturnVal,
                'Could not run command '.$composerCmd
                .'. Output was: '.PHP_EOL
                .implode(PHP_EOL, $composerOutput));
        } finally {
            if (!chdir($currentDir)) {
                throw new \Exception('Could not chdir back to '
                    .escapeshellarg($currentDir).'.');
            }
        }
    }

    protected function postComposerInstallSymlinkTest($rootPkgDir)
    {
        $this->assertCorrectSymlinks($rootPkgDir);
    }

    protected function assertCorrectSymlinks($rootPkgDir)
    {
        $vendorDir = $rootPkgDir.DIRECTORY_SEPARATOR.'vendor';
        $innerDepDir = $vendorDir.DIRECTORY_SEPARATOR
            .'viison/composer-rules-plugin-test-inner-dep';
        $outerDepDir = $vendorDir.DIRECTORY_SEPARATOR
            .'viison/composer-rules-plugin-test-outer-dep';

        if ($vendorDir === false) {
            throw new \Exception('Vendor directory does not exist at '
                .escapeshellarg($vendorDir).'.');
        }

        if ($innerDepDir === false) {
            throw new \Exception(
                'The directory for the inner dependency does not exist at '
                .escapeshellarg($innerDepDir).'.');
        }

        if ($outerDepDir === false) {
            throw new \Exception(
                'The directory for the outer dependency does not exist at '
                .escapeshellarg($outerDepDir).'.');
        }

        $realInnerDepDir = realpath($innerDepDir);
        $realOuterDepDir = realpath($outerDepDir);

        if ($realInnerDepDir === false) {
            throw new \Exception('Real path for inner dependency could not be'
                .' determined ('.escapeshellarg($innerDepDir).')');
        }

        if ($realOuterDepDir === false) {
            throw new \Exception('Real path for outer dependency could not be'
            .' determined ('.escapeshellarg($outerDepDir).')');
        }

        $link = $realOuterDepDir.DIRECTORY_SEPARATOR.'InnerDep';

        if (!file_exists($link)) {
            throw new \Exception(escapeshellarg($link)
            .' is not a symbolic link. The file does not exist.');
        }

        if (!@is_link($link)) {
            throw new \Exception(escapeshellarg($link)
            .' is not a symbolic link but a '.filetype($link).'.');
        }

        $linkTarget = readlink($link);

        if ($linkTarget === false) {
            throw new \Exception('Link target of '.escapeshellarg($link)
            .' could not be determined.');
        }

        $currentDir = getcwd();
        try {
            chdir($realOuterDepDir);
            $realLinkTarget = realpath($linkTarget);
            if ($realLinkTarget !== $realInnerDepDir) {
                throw new \Exception(escapeshellarg($link)
                    .' does not link to '.escapeshellarg($realInnerDepDir)
                    .', but to '.escapeshellarg($linkTarget)
                    .' (realpath = '.escapeshellarg($realLinkTarget).')');
            }
        } finally {
            if (!chdir($currentDir)) {
                throw new \Exception('Could not chdir back to '
                    .escapeshellarg($currentDir).'.');
            }
        }
    }

    protected function postComposerInstallUpdateOuterDepTest($rootPkgDir)
    {
    }

    protected function postComposerUpdateUpdateOuterDepTest($rootPkgDir)
    {
        $this->assertCorrectSymlinks($rootPkgDir);
    }
}
