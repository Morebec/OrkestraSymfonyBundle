<?php


namespace Morebec\OrkestraSymfonyBundle\Command;

use Morebec\Orkestra\Messaging\Routing\DomainMessageRouteInterface;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console Command used to debug domain message routes.
 */
class DebugDomainMessageRouter extends Command
{
    protected static $defaultName = 'orkestra:messaging:router';

    /**
     * @var DomainMessageRouterInterface
     */
    private $router;

    public function __construct(DomainMessageRouterInterface $router)
    {
        parent::__construct();
        $this->router = $router;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $routes = $this->router->getRoutes();

        // Organize routes per message.
        $messages = [];

        /** @var DomainMessageRouteInterface $route */
        foreach ($routes as $route) {
            $messageTypeName = $route->getDomainMessageTypeName();
            if(!array_key_exists($messageTypeName, $messages)) {
                $messages[$messageTypeName] = [];
            }
            $messages[$messageTypeName][] = $route->getDomainMessageHandlerClassName() . '::' . $route->getDomainMessageHandlerMethodName();
        }

        if ($messages) {
            foreach ($messages as $message => $messageRoutes) {
                $io->text("<info>{$message}</info>");
                $io->listing($messageRoutes);
            }
        } else {
            $io->warning('No routes are defined');
        }

        return self::SUCCESS;
    }
}