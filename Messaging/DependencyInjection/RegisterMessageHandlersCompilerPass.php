<?php

namespace Morebec\OrkestraBundle\Messaging\DependencyInjection;

use Morebec\Orkestra\Messaging\MessageHandlerMap;
use Morebec\Orkestra\Messaging\MessageHandlerProvider;
use Morebec\OrkestraBundle\DependencyInjection\OrkestraExtension;
use Morebec\OrkestraBundle\OrkestraBundle;
use Morebec\Orkestra\Messaging\Command\CommandInterface;
use Morebec\Orkestra\Messaging\Event\EventInterface;
use Morebec\Orkestra\Messaging\Notification\NotificationInterface;
use Morebec\Orkestra\Messaging\Query\QueryInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This container compiler pass is used to register with a message bus
 * all handlers of a given type that have multiple handling methods for a given subtype.
 * As a usage example:
 * new AutoRegisterMessageHandlersCompilerPass()
 * Would associate all services tagged with event_handler, to EventInterface messages.
 */
class RegisterMessageHandlersCompilerPass implements CompilerPassInterface
{
    /**
     * @var OrkestraExtension
     */
    private $extension;

    public function __construct(OrkestraExtension $extension)
    {
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        // Find all buses
        $busIds = [];
        foreach ($container->findTaggedServiceIds('messenger.bus') as $busId => $tags) {
            $busIds[] = $busId;
        }

        // Initialise map by bus
        $handlerMethodsByMessageTypeByBus = [];
        foreach ($busIds as $busId) {
            $handlerMethodsByMessageTypeByBus[$busId] = [];
        }

        $handlerProvider = $container->getDefinition(MessageHandlerProvider::class);

        // Process each configuration
        $configuration = $this->loadConfig();
        foreach ($configuration as $config) {
            $busId = $config['message_bus'];
            $serviceTag = $config['service_tag'];
            $messageClass = $config['message_class'];

            // Make sure the bus referenced exists
            if (!\in_array($busId, $busIds, true)) {
                throw new \RuntimeException(sprintf("Invalid message bus '%s': Cannot associate services tagged '%s' with bus '%s' as it does not exist. Did you forget to register it in the messenger component ? (available buses: [%s])", $busId, $serviceTag, $busId, implode(', ', $busIds)));
            }

            foreach ($container->findTaggedServiceIds($serviceTag, true) as $serviceId => $tags) {
                // Check if there aren't any tags overriding this compiler pass's configuration
                foreach ($tags as $tag) {
                    if (isset($tag['bus'])) {
                        // We have an override
                        $busId = $tag['bus'];

                        // Make sure it exists
                        if (!\in_array($tag['bus'], $busIds, true)) {
                            throw new \RuntimeException(sprintf("Invalid handler service '%s': Cannot associate handler with bus '%s' as it does not exist. Did you forget to register it in the messenger component ? (available buses: [%s])", $serviceId, $busId, implode(', ', $busIds)));
                        }
                    }
                }

                $definition = $container->getDefinition($serviceId);
                /** @var class-string<object> $class */
                $class = $definition->getClass();
                if (!$class) {
                    throw new \RuntimeException("Class for service $serviceId not found, did you register it correctly ?");
                }

                // Map Messages to handler methods
                $messageHandlerClass = new ReflectionClass($class);
                $handlerMethodsByMessageType = $this->mapHandlerMethodsByMessageType($messageHandlerClass, $messageClass);
                $handlerMethodsByMessageTypeByBus[$busId] = array_merge_recursive($handlerMethodsByMessageTypeByBus[$busId], $handlerMethodsByMessageType);

                // Register with handler provider
                $handlerProvider->addMethodCall('addHandler', [new Reference($serviceId)]);
            }

            $handlerMapDefinition = $container->getDefinition(MessageHandlerMap::class);

            $handlerMapDefinition->setArgument(0, array_merge(...array_values($handlerMethodsByMessageTypeByBus)));
        }

        $this->applyDebugMapToCommand($container, $handlerMethodsByMessageTypeByBus);
    }

    /**
     * Returns a map of handling methods by their message type for a given class
     * For a method to be considered, it must be public and it must
     * require a single non optional class type-hinted parameter.
     * Return value structure
     *  [
     *      Message::class => [$messageHandlerClass::method],
     *      OtherMessage::class => [$messageHandlerClass::otherMethod]
     *  ].
     *
     * @param ReflectionClass<object> $messageHandlerClass
     *
     * @return array<class-string<object>, array<int, string>>
     */
    protected function mapHandlerMethodsByMessageType(ReflectionClass $messageHandlerClass, string $messageClass): array
    {
        $methods = $messageHandlerClass->getMethods(ReflectionMethod::IS_PUBLIC);
        $handledMethodsByMessageType = [];
        foreach ($methods as $method) {
            if ($method->isConstructor() || $method->isDestructor()) {
                continue;
            }

            $params = $method->getParameters();

            // Skip methods with no or more than one parameters OR methods where the parameter is optional OR does not have a class
            if (\count($params) !== 1 || $params[0]->isOptional() || $params[0]->getClass() === null) {
                continue;
            }
            $paramClass = $params[0]->getClass();

            // Ensure this is a subclass of what we are looking to handle
            if (!$paramClass->isSubclassOf($messageClass)) {
                continue;
            }

            /*$handlerMapDefinition->addMethodCall('registerHandler', [
                $paramClass->getName(),
                $messageHandlerClass->getName(),
                $method->getName()
            ]);*/

            if (!\array_key_exists($paramClass->getName(), $handledMethodsByMessageType)) {
                $handledMethodsByMessageType[$paramClass->getName()] = [];
            }
            $handlerFQDN = "{$messageHandlerClass->getName()}::{$method->getName()}";
            $handledMethodsByMessageType[$paramClass->getName()][] = $handlerFQDN;
        }

        return $handledMethodsByMessageType;
    }

    /**
     * Applies the map to the debug command.
     *
     * @param ContainerBuilder                                                  $container
     *                                                                                                            param array<int|string, array<int|string, string>> $handlerMethodsByMessageTypeByBus
     * @param array<int|string,array<class-string<object>, array<int, string>>> $handlerMethodsByMessageTypeByBus
     */
    protected function applyDebugMapToCommand(ContainerBuilder $container, array $handlerMethodsByMessageTypeByBus): void
    {
        if ($container->hasDefinition('console.command.messenger_debug')) {
            $debugCommandMapping = $handlerMethodsByMessageTypeByBus;

            foreach ($handlerMethodsByMessageTypeByBus as $busId => $handlerByMessages) {
                /** @var iterable<string> $handlers */
                foreach ($handlerByMessages as $message => $handlers) {
                    foreach ($handlers as $key => $handler) {
                        $debugCommandMapping[$busId][$message][$key] = ["<fg=green;options=underscore>{$handler}</></>", []];
                    }
                }
            }
            $oldDebug = $container->getDefinition('console.command.messenger_debug')->getArgument(0);

            $debugCommandMapping = array_merge($oldDebug, $debugCommandMapping);
            $container->getDefinition('console.command.messenger_debug')->replaceArgument(0, $debugCommandMapping);
        }
    }

    /**
     * [
     *      ['service_tag' => 'event_handler', 'message_class' => EventInterface::class, 'message_bus' => event.bus']
     *      ['service_tag' => 'command_handler', 'message_class' => CommandInterface::class, ''message_bus' => 'command.bus']
     * ].
     *
     * @return array[]
     */
    private function loadConfig(): array
    {
        $config = $this->extension->getConfig();

        return [
            [
                'service_tag' => OrkestraBundle::COMMAND_HANDLER_TAG,
                'message_class' => CommandInterface::class,
                'message_bus' => $config['command_bus'],
            ],
            [
                'service_tag' => OrkestraBundle::QUERY_HANDLER_TAG,
                'message_class' => QueryInterface::class,
                'message_bus' => $config['query_bus'],
            ],
            [
                'service_tag' => OrkestraBundle::EVENT_HANDLER_TAG,
                'message_class' => EventInterface::class,
                'message_bus' => $config['event_bus'],
            ],
            [
                'service_tag' => OrkestraBundle::WORKFLOW_TAG,
                'message_class' => EventInterface::class,
                'message_bus' => $config['event_bus'],
            ],
            [
                'service_tag' => OrkestraBundle::NOTIFICATION_HANDLER_TAG,
                'message_class' => NotificationInterface::class,
                'message_bus' => $config['notification_bus'],
            ],
        ];
    }
}
