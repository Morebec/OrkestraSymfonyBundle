<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\Routing\DomainMessageRoute;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouteCollection;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouteInterface;
use Morebec\Orkestra\Messaging\Routing\DomainMessageRouterInterface;
use Morebec\Orkestra\Messaging\Routing\Tenant\TenantSpecificRoute;
use Morebec\Orkestra\Normalization\Denormalizer\DenormalizationContext;
use Morebec\Orkestra\Normalization\Denormalizer\DenormalizationContextInterface;
use Morebec\Orkestra\Normalization\Denormalizer\DenormalizerInterface;
use Morebec\Orkestra\Normalization\ObjectNormalizer;
use Psr\Container\ContainerInterface;

/**
 * Service responsible for saving the routes of the {@link DomainMessageRouterInterface}
 *in symfony's cache at the {@link ContainerInterface}'s compile time.
 */
class DomainMessageRouterCache
{
    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var ObjectNormalizer
     */
    private $normalizer;

    public function __construct(string $cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
        $this->normalizer = new ObjectNormalizer();

        $this->normalizer->addDenormalizer(new class() implements DenormalizerInterface {

            public function denormalize(DenormalizationContextInterface $context)
            {
                $data = $context->getValue();


                if (array_key_exists('tenantId', $data)) {
                    $override = null;

                    if (array_key_exists('overridesRoute', $data)) {
                        $override = $this->denormalize(new DenormalizationContext(
                            $data['overridesRoute'],
                            DomainMessageRouteInterface::class,
                            $context
                        ));
                    }

                    return new TenantSpecificRoute(
                        $data['tenantId'],
                        $data['domainMessageTypeName'],
                        $data['messageHandlerClassName'],
                        $data['messageHandlerMethodName'],
                        $override
                    );
                }

                return new DomainMessageRoute(
                    $data['domainMessageTypeName'],
                    $data['messageHandlerClassName'],
                    $data['messageHandlerMethodName']
                );
            }

            public function supports(DenormalizationContextInterface $context): bool
            {
                return $context->getTypeName() === DomainMessageRouteInterface::class;
            }
        });
    }

    /**
     * Dumps the routes to the cache.
     * @param DomainMessageRouteCollection $routes
     */
    public function dumpRoutes(DomainMessageRouteCollection $routes): void
    {
        // Normalize routes
         $data = json_encode($this->normalizer->normalize($routes->toArray())/*, JSON_PRETTY_PRINT*/);
        file_put_contents($this->getCacheFile(), $data);
    }

    /**
     * Loads the routes back from the cache.
     * @return DomainMessageRouteCollection
     */
    public function loadRoutes(): DomainMessageRouteCollection
    {
        $data = json_decode(file_get_contents($this->getCacheFile()), true);
        $routes = [];
        foreach ($data as $datum) {
            $routes[] = $this->normalizer->denormalize($datum, DomainMessageRouteInterface::class);
        }

        return new DomainMessageRouteCollection($routes);
    }

    /**
     * @return string
     */
    protected function getCacheFile(): string
    {
        return $this->cacheDirectory . '/domain_message_routes.json';
    }
}