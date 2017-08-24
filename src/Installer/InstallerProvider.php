<?php
/**
 * Copyright (c) 2016 Axel Helmert
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
 * @copyright Copyright (c) 2016 Axel Helmert
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */

namespace Rampage\Nexus\Node\Installer;

use Rampage\Nexus\Package\ComposerPackage;
use Rampage\Nexus\Package\ZpkPackage;
use Rampage\Nexus\Package\PackageInterface;
use Rampage\Nexus\Package\Installer\InstallerInterface;
use Rampage\Nexus\Package\Installer\InstallerProviderInterface;

use Rampage\Nexus\Exception\RuntimeException;
use Rampage\Nexus\Exception\LogicException;

use Interop\Container\ContainerInterface;


/**
 * Application package manager to retrieve the installer implementation for a package file
 */
class InstallerProvider implements InstallerProviderInterface
{
    /**
     * Contains the installer classes indexed by package type
     *
     * @var string[]
     */
    protected $installerClasses = [];

    /**
     * Installer prototypes indexed by package type
     *
     * @var InstallerInterface[]
     */
    protected $prototypes = [];

    /**
     * IoC container
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param PackageStorage $packageStorage
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->installerClasses = [
            ComposerPackage::TYPE_COMPOSER => ComposerInstaller::class,
            ZpkPackage::TYPE_ZPK => ZpkInstaller::class,
        ];
    }

    /**
     * @param InstallerInterface $packageType
     */
    public function addInstaller($packageType, $class)
    {
        $this->installerClasses[$packageType] = $class;
        return $this;
    }

    /**
     * Creates the installer prototype
     *
     * @param   string              $type   The package type
     * @return  InstallerInterface
     * @throws  RuntimeException
     */
    protected function createInstallerPrototype($type)
    {
        if (!isset($this->installerClasses[$type])) {
            throw new RuntimeException('Unsupported package type: ' . $type);
        }

        $installer = $this->container->get($this->installerClasses[$type]);

        if (!$installer instanceof InstallerInterface) {
            $type = is_object($installer)? get_class($installer) : gettype($installer);
            throw new LogicException('Invalid installer implementation: ' . $type);
        }

        $this->prototypes[$type] = $installer;
    }

    /**
     * @return string[]
     */
    public function getSupportedPackageTypes()
    {
        return array_keys($this->installerClasses);
    }

    /**
     * @param PackageInterface $package
     * @return InstallerInterface
     */
    public function getInstaller(PackageInterface $package)
    {
        $type = $package->getType();

        if (!isset($this->prototypes[$type])) {
            $this->createInstallerPrototype($type);
        }

        return clone $this->prototypes[$type];
    }
}
