<?php
/**
 * OpenConnector Application Class
 *
 * This file contains the main application class for the OpenConnector application.
 *
 * @category  AppInfo
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

declare(strict_types=1);

namespace OCA\OpenConnector\AppInfo;

use OCA\OpenConnector\EventListener\ObjectCreatedEventListener;
use OCA\OpenConnector\EventListener\ObjectDeletedEventListener;
use OCA\OpenConnector\EventListener\ObjectUpdatedEventListener;
use OCA\OpenConnector\Service\SynchronizationObjectProcessor;
use OCA\OpenConnector\Service\TargetHandler\ApiHandler;
use OCA\OpenConnector\Service\TargetHandler\OpenRegisterHandler;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;

/**
 * Main Application class for the OpenConnector app
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'openconnector';


    /**
     * Constructor for the Application class
     *
     * Initializes the application with its ID
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(self::APP_ID);

    }//end __construct()


    /**
     * Register application components during app initialization
     *
     * @param IRegistrationContext $context The registration context
     *
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        include_once __DIR__.'/../../vendor/autoload.php';

        // Register target handlers.
        $context->registerService(
            TargetHandlerRegistry::class,
            function ($c) {
                $registry = new TargetHandlerRegistry($c);
                $registry->registerHandler($c->get(OpenRegisterHandler::class));
                $registry->registerHandler($c->get(ApiHandler::class));
                return $registry;
            }
        );

        // Register event listeners.
        $dispatcher = $this->getContainer()->get(IEventDispatcher::class);
        $dispatcher->addServiceListener(eventName: ObjectCreatedEvent::class, className: ObjectCreatedEventListener::class);
        $dispatcher->addServiceListener(eventName: ObjectUpdatedEvent::class, className: ObjectUpdatedEventListener::class);
        $dispatcher->addServiceListener(eventName: ObjectDeletedEvent::class, className: ObjectDeletedEventListener::class);

    }//end register()


    /**
     * Boot the application after registration is done
     *
     * @param IBootContext $context The boot context
     *
     * @return void
     */
    public function boot(IBootContext $context): void
    {

    }//end boot()


}//end class
