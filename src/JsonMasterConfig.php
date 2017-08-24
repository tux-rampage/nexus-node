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

use Rampage\Nexus\Exception\RuntimeException;
use Zend\Stdlib\Parameters;


class JsonMasterConfig implements MasterConfigInterface
{
    /**
     * Defines the properties that are read from the json file to the object properties
     * @var array
     */
    private static $loadProperties = [
        'masterSecret',
        'masterUrl',
        'nodeId',
        'nodeSecret'
    ];

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $nodeId = null;

    /**
     * @var string
     */
    private $masterSecret = null;

    /**
     * @var string
     */
    private $masterUrl = null;

    /**
     * @var string
     */
    private $nodeSecret = null;

    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->readJsonData();
    }

    /**
     * Read data from json file
     */
    private function readJsonData()
    {
        if (!is_readable($this->file)) {
            return;
        }

        $data = @json_decode($this->file, true);
        $params = new Parameters($data);

        foreach (self::$loadProperties as $property) {
            $this->{$property} = $params[$property];
        }
    }

    /**
     * @throws RuntimeException
     * @return void
     */
    private function saveJsonData()
    {
        $data = [];

        foreach (self::$loadProperties as $property) {
            $data[$property] = $this->{$property};
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!file_put_contents($this->file, $json)) {
            throw new RuntimeException('Could not write master config file: ' . $this->file);
        }
    }

    /**
     * @return void
     */
    private function reset()
    {
        foreach (self::$loadProperties as $property) {
            $this->{$property} = null;
        }
    }

    /**
     * @param string $nodeId
     * @param string $nodeSecret
     * @param string $masterUrl
     * @param string $masterSecret
     * @return self
     */
    public function create($nodeId, $nodeSecret, $masterUrl, $masterSecret)
    {
        $this->nodeId = $nodeId;
        $this->nodeSecret = $nodeSecret;
        $this->masterUrl = $masterUrl;
        $this->masterSecret = $masterSecret;

        $this->saveJsonData();

        return $this;
    }

    /**
     * Reload data
     */
    public function reload()
    {
        $this->reset();
        $this->readJsonData();
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::clear()
     */
    public function clear()
    {
        $this->reset();
        @unlink($this->file);
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::getMasterSecret()
     */
    public function getMasterSecret()
    {
        return $this->masterSecret;
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::getMasterUrl()
     */
    public function getMasterUrl()
    {
        return $this->masterUrl;
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::getNodeId()
     */
    public function getNodeId()
    {
        return $this->nodeId;
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::getNodeSecret()
     */
    public function getNodeSecret()
    {
        return $this->nodeSecret;
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\Node\MasterConfigInterface::hasMaster()
     */
    public function hasMaster()
    {
        return ($this->masterSecret
                && $this->masterUrl
                && $this->nodeId
                && $this->nodeSecret);
    }
}
