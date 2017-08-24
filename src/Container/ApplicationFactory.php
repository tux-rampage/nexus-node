<?php
/**
 * @author    Axel Helmert
 * @copyright Copyright (c) 2017 Axel Helmert
 * @license   http://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License
 */

namespace Rampage\Nexus\Node\Contaier;

use Rampage\Nexus\ServiceFactory\Middleware\AbstractApplicationFactory;
use Rampage\Nexus\Node\Action\IndexAction;
use Rampage\Nexus\Node\Middleware\AuthenticationMiddleware;

use Psr\Container\ContainerInterface;

use Zend\Expressive\Application;
use Zend\Expressive\Middleware\NotFoundHandler;
use Zend\Stratigility\Middleware\ErrorHandler as ErrorHandlerMiddleware;


class ApplicationFactory extends AbstractApplicationFactory
{
    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\ServiceFactory\Middleware\AbstractApplicationFactory::createMiddlewarePipe()
     */
    protected function createMiddlewarePipe(Application $application, ContainerInterface $container)
    {
        $application->pipe(ErrorHandlerMiddleware::class);
        $application->pipe(AuthenticationMiddleware::class);
        $application->pipeRoutingMiddleware();
        $application->pipeDispatchMiddleware();
        $application->pipe(NotFoundHandler::class);
    }

    /**
     * {@inheritDoc}
     * @see \Rampage\Nexus\ServiceFactory\Middleware\AbstractApplicationFactory::createRoutingDefinition()
     */
    protected function createRoutingDefinition(Application $application, ContainerInterface $container)
    {
        $application->route('/', IndexAction::class, ['GET'], 'index');
    }


}
