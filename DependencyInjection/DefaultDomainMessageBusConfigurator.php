<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Context\BuildDomainContextMiddleware;
use Morebec\Orkestra\Messaging\DomainMessageBusInterface;
use Morebec\Orkestra\Messaging\Middleware\LoggerMiddleware;
use Morebec\Orkestra\Messaging\Routing\HandleDomainMessageMiddleware;
use Morebec\Orkestra\Messaging\Scheduling\ScheduleDomainMessageMiddleware;

/**
 * Default implementation of the {@link DomainMessageBusConfiguratorInterface} that provides the default middleware:
 * - {@link BuildDomainContextMiddleware}
 * - {@link LoggerMiddleware}
 * - {@link HandleDomainMessageMiddleware}.
 */
class DefaultDomainMessageBusConfigurator implements DomainMessageBusConfiguratorInterface
{
    /**
     * @var BuildDomainContextMiddleware
     */
    protected $buildDomainContextMiddleware;

    /**
     * @var LoggerMiddleware
     */
    protected $loggerMiddleware;

    /**
     * @var HandleDomainMessageMiddleware
     */
    protected $handleDomainMessageMiddleware;

    /**
     * @var ScheduleDomainMessageMiddleware
     */
    protected $scheduleDomainMessageMiddleware;


    public function __construct(
        BuildDomainContextMiddleware $buildDomainContextMiddleware,
        LoggerMiddleware $loggerMiddleware,
        ScheduleDomainMessageMiddleware $scheduleDomainMessageMiddleware,
        HandleDomainMessageMiddleware $handleDomainMessageMiddleware
    )
    {
        $this->buildDomainContextMiddleware = $buildDomainContextMiddleware;
        $this->loggerMiddleware = $loggerMiddleware;
        $this->handleDomainMessageMiddleware = $handleDomainMessageMiddleware;
        $this->scheduleDomainMessageMiddleware = $scheduleDomainMessageMiddleware;
    }

    /**
     * @inheritDoc
     */
    public function configure(DomainMessageBusInterface $domainMessageBus): void
    {
        $domainMessageBus->appendMiddleware($this->buildDomainContextMiddleware);
        $this->configureMiddleware($domainMessageBus);
        $domainMessageBus->appendMiddleware($this->handleDomainMessageMiddleware);
    }

    /**
     * Configures the replaceable middleware
     * @param DomainMessageBusInterface $domainMessageBus
     */
    protected function configureMiddleware(DomainMessageBusInterface $domainMessageBus): void
    {
        $domainMessageBus->appendMiddleware($this->loggerMiddleware);
        $domainMessageBus->appendMiddleware($this->scheduleDomainMessageMiddleware);
    }
}