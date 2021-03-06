<?php
/**
 * Copyright (c) 2017 Axel Helmert
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
 * @copyright Copyright (c) 2017 Axel Helmert
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */

namespace Rampage\Nexus\Node;

use Rampage\Nexus\BootstrapTrait;
use Rampage\Nexus\Config\ConfigProviderInterface;

/**
 * Node bootstrapper
 */
class Bootstrap
{
    use BootstrapTrait;

    public function __construct(ConfigProviderInterface $configProvider = null)
    {
        // Work around PHP's awful behaviour triggering a warning when the timezone is not set
        date_default_timezone_set(@date_default_timezone_get());
        $this->createContainer($configProvider?: new ConfigProvider($_SERVER));
    }
}
