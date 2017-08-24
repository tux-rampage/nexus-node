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

use Rampage\Nexus\Node\Repository\ApplicationRepositoryInterface;
use Rampage\Nexus\Version;
use Rampage\Nexus\Entities\ApplicationInstance;
use Rampage\Nexus\Deployment\NodeInterface;

use JsonSerializable;


class NodeInfo implements JsonSerializable
{
    /**
     * @var ApplicationRepositoryInterface
     */
    private $appRepository;

    /**
     * @var Version
     */
    private $appVersion;

    /**
     * @var MasterConfigInterface
     */
    private $masterConfig;

    /**
     * @var string
     */
    private $aggregatedState = null;

    /**
     * @param ApplicationRepositoryInterface $appRepository
     * @param Version $appVersion
     * @param MasterConfigInterface $masterConfig
     */
    public function __construct(ApplicationRepositoryInterface $appRepository,
        Version $appVersion,
        MasterConfigInterface $masterConfig)
    {
        $this->appRepository = $appRepository;
        $this->appVersion = $appVersion;
        $this->masterConfig = $masterConfig;
    }

    public function getId()
    {
        return $this->masterConfig->getNodeId();
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->appVersion->getVersion();
    }

    /**
     * @return string
     */
    private function aggregateState()
    {
        $default = NodeInterface::STATE_UNINITIALIZED;

        foreach ($this->appRepository->findAll() as $app) {
            $default = NodeInterface::STATE_READY;

            if ($app->getState() == ApplicationInstance::STATE_ERROR) {
                return NodeInterface::STATE_FAILURE;
            }

            if (!in_array($app->getState(), [ApplicationInstance::STATE_DEPLOYED, ApplicationInstance::STATE_REMOVED])) {
                return NodeInterface::STATE_BUILDING;
            }
        }

        return $default;
    }

    /**
     * @return string
     */
    public function getState()
    {
        if ($this->aggregatedState === null) {
            $this->aggregatedState = $this->aggregatedState;
        }

        return $this->aggregatedState;
    }

    /**
     * @return string[]
     */
    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'state' => $this->getState(),
            'version' => $this->getVersion()
        ];
    }

    /**
     * {@inheritDoc}
     * @see JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
