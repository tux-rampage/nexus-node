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

namespace Rampage\Nexus\Node\Middleware;

use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\TextResponse;
use Rampage\Nexus\Node\MasterConfigInterface;


class AuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @var MasterConfigInterface
     */
    private $masterConfig;

    /**
     * @param MasterConfigInterface $masterConfig
     */
    public function __construct(MasterConfigInterface $masterConfig)
    {
        $this->masterConfig = $masterConfig;
    }
    /**
     * Check if the request has a valid authentication
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $token = $request->getHeaderLine('Authorization');

        if (!$token || ($token != $this->masterConfig->getMasterSecret())) {
            return new TextResponse('Unauthorized', 401);
        }

        return $delegate->process($request);
    }
}
