<?php

namespace Morebec\OrkestraBundle;

use Morebec\OrkestraBundle\Console\DependencyInjection\RegisterConsoleCommandCompilerPass;
use Morebec\OrkestraBundle\DependencyInjection\OrkestraExtension;
use Morebec\OrkestraBundle\Messaging\DependencyInjection\RegisterMessageHandlersCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OrkestraBundle extends Bundle
{
    public const COMMAND_HANDLER_TAG = 'command_handler';

    public const EVENT_HANDLER_TAG = 'event_handler';

    public const QUERY_HANDLER_TAG = 'query_handler';

    public const WORKFLOW_TAG = 'workflow';

    public const NOTIFICATION_HANDLER_TAG = 'notification_handler';

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Register compiler passes
        $container->addCompilerPass(new RegisterMessageHandlersCompilerPass($this->extension));
        $container->addCompilerPass(new RegisterConsoleCommandCompilerPass());
    }

    /**
     * @return mixed
     */
    public function getContainerExtensionClass()
    {
        return OrkestraExtension::class;
    }
}
