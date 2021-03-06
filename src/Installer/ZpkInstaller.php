<?php
/**
 * Copyright (c) 2015 Axel Helmert
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Axel Helmert
 * @copyright Copyright (c) 2015 Axel Helmert
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */
namespace Rampage\Nexus\Node\Installer;

use Rampage\Nexus\Exception;
use Rampage\Nexus\Deployment\StageSubscriberInterface;
use Rampage\Nexus\FileSystem;
use Rampage\Nexus\Archive\ArchiveLoaderInterface;
use Rampage\Nexus\Package\ZpkPackage;


/**
 * Implements an instaler for ZendServer Packages
 */
class ZpkInstaller extends AbstractInstaller implements StageSubscriberInterface
{
    const STAGE_PRE_INSTALL     = 'pre_stage';
    const STAGE_POST_INSTALL    = 'post_stage';
    const STAGE_PRE_ACTIVATE    = 'pre_activate';
    const STAGE_POST_ACTIVATE   = 'post_activate';
    const STAGE_PRE_DEACTIVATE  = 'pre_deactivate';
    const STAGE_POST_DEACTIVATE = 'post_deactivate';
    const STAGE_PRE_REMOVE      = 'pre_remove';
    const STAGE_POST_REMOVE     = 'post_remove';
    const STAGE_PRE_ROLLBACK    = 'pre_rollback';
    const STAGE_POST_ROLLBACK   = 'post_rollback';

    /**
     * @var string
     */
    protected $extractedScriptsPath = null;

    /**
     * @var Zpk\ConfigInterface
     */
    protected $config;

    /**
     * @var FileSystem
     */
    protected $filesystem;

    /**
     * @param zpk\Config $config
     */
    public function __construct(ArchiveLoaderInterface $archiveLoader, Zpk\ConfigInterface $config = null)
    {
        $this->config = $config? : new Zpk\Config();
        $this->filesystem = new FileSystem();

        parent::__construct($archiveLoader);
    }

    /**
     * Destruct
     */
    public function __destruct()
    {
        if ($this->extractedScriptsPath) {
            $this->filesystem->delete($this->extractedScriptsPath);
        }
    }

    /**
     * @see \rampage\nexus\package\InstallerInterface::getWebRoot()
     */
    public function getWebRoot($params)
    {
        $this->assertArchive();
        $this->assertTargetDirectory();

        $package = $this->package;
        $docRoot = $package->getDocumentRoot();
        $appDir =  trim($package->getExtra(ZpkPackage::EXTRA_APP_DIR), '/');

        if (strpos($docRoot, $appDir . '/') === 0) {
            $docRoot = substr($docRoot, strlen($appDir) + 1);
        }

        return rtrim($this->targetDirectory->getPathname()) . '/' . ltrim($docRoot);
    }

    /**
     * @param string $targetDir
     * @param string $subDir
     */
    protected function extract($targetDir, $subDir = null)
    {
        if (!$subDir) {
            $this->archive->extractTo($targetDir);
            return;
        }

        $mode = $this->config->getDirCreateMode();
        $targetDir = rtrim($targetDir, '/');
        $prefix = 'phar://' . $this->archive->getRealPath() . '/' . trim($subDir, '/') . '/';
        $prefixLen = strlen($prefix);
        $prefixFilter = function(\PharFileInfo $file, $key, $iterator) use ($subDir, $prefix) {
            return (strpos($file->getPathname(), $prefix) === 0);
        };

        $iterator = new \CallbackFilterIterator(new \RecursiveIteratorIterator($this->archive, \RecursiveIteratorIterator::SELF_FIRST), $prefixFilter);

        /* @var $file \PharFileInfo */
        foreach ($iterator as $file) {
            $relativePath = substr($file->getPathname(), $prefixLen);
            $targetPath   = $targetDir . '/' . $relativePath;

            if ($file->isDir()) {
                if (!is_dir($targetPath) && !mkdir($targetPath, $mode, true)) {
                    throw new Exception\RuntimeException(sprintf('Failed to create directory: "%s"', $targetPath));
                }

                continue;
            }

            $targetDirPath = dirname($targetPath);
            if (!is_dir($targetDirPath) && !mkdir($targetDirPath, $mode, true)) {
                throw new Exception\RuntimeException(sprintf('Failed to create directory: "%s"', $targetDirPath));
            }

            file_put_contents($targetPath, $file->getContent());
        }
    }

    /**
     * @return self
     */
    protected function extractScriptsDir()
    {
        if ($this->extractedScriptsPath !== null) {
            return $this;
        }

        $scripts = $this->getPackage()->getScriptsDir();
        $dir = sys_get_temp_dir();

        $this->extractedScriptsPath = tempnam($dir, 'zpk.scripts');
        $this->extract($this->extractedScriptsPath, $scripts);

        return $this;
    }

    /**
     * Execute a hook script if it exists
     *
     * @param string  $name
     * @param array   $params
     */
    protected function runHookScript($name, array $params)
    {
        $this->extractScriptsDir();

        $path = $this->extractedScriptsPath . '/' . $name . '.php';

        if (!is_readable($path)) {
            return;
        }

        $variables = $this->package->getVariables();
        $invoker = new Zpk\StageScript($path, $this->config, $params, $this->targetDirectory->getPathname(), $this->getPackage()->getVersion(), $variables);

        if (!$invoker->execute(true)) {
            throw new Exception\StageScriptException(sprintf('Stage script "%s" failed.', $name));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function install($params)
    {
        $this->runHookScript(self::STAGE_PRE_INSTALL, $params);

        $package = $this->getPackage();
        $appDir = trim($package->getAppDir(), '/');

        $this->extract($this->targetDirectory->getPathname(), $appDir);

        $this->runHookScript(self::STAGE_POST_INSTALL, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($params)
    {
        $this->runHookScript(self::STAGE_PRE_REMOVE, $params);
        $this->filesystem->delete($this->targetDirectory->getPathname());
        $this->runHookScript(self::STAGE_POST_REMOVE, $params);
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::afterActivate()
     */
    public function afterActivate($params)
    {
        $this->runHookScript(self::STAGE_POST_ACTIVATE, $params);
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::afterDeactivate()
     */
    public function afterDeactivate($params)
    {
        $this->runHookScript(self::STAGE_POST_DEACTIVATE, $params);
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::afterRollback()
     */
    public function afterRollback($params, $isRollbackTarget)
    {
        if ($isRollbackTarget) {
            $this->runHookScript(self::STAGE_POST_ROLLBACK, $params);
        }
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::beforeActivate()
     */
    public function beforeActivate($params)
    {
        $this->runHookScript(self::STAGE_PRE_ACTIVATE, $params);
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::beforeDeactivate()
     */
    public function beforeDeactivate($params)
    {
        $this->runHookScript(self::STAGE_PRE_DEACTIVATE, $params);
    }

    /**
     * @see \rampage\nexus\node\installer\StageSubscriberInterface::beforeRollback()
     */
    public function beforeRollback($params, $isRollbackTarget)
    {
        if ($isRollbackTarget) {
            $this->runHookScript(self::STAGE_PRE_ROLLBACK, $params);
        }
    }
}
