<?php


namespace Morebec\OrkestraSymfonyBundle\DependencyInjection;

use Morebec\Orkestra\Messaging\DomainMessageInterface;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageClassMap;
use Morebec\Orkestra\Messaging\Normalization\DomainMessageClassMapInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Factory Responsible for instantiating the {@link DomainMessageClassMap}.
 * It uses reflection to determine the domain type names and the classes to use.
 * It saves this in Symfony's cache for performance.
 */
class SymfonyDomainMessageClassMapFactory
{
     private const CACHE_KEY = 'domain_message_class_map';

    /**
     * Project's source directory.
     *
     * @var string
     */
    private $sourceDir;

    /**
     * @var CacheInterface
     */
    private $cache;

    public function __construct(ParameterBagInterface $parameterBag, CacheInterface $cache)
    {
        $this->sourceDir = $parameterBag->get('kernel.project_dir').'/src/';
        $this->cache = $cache;
    }

    /**
     * Generates the registry.
     */
    public function buildClassMap(): DomainMessageClassMapInterface
    {
        $sourceDir = $this->sourceDir;
        $map = $this->cache->get(self::CACHE_KEY, static function (ItemInterface $item) use ($sourceDir) {
            $item->expiresAfter(null);
            $classes = ClassDiscoverer::discover($sourceDir);

            $map = [];
            foreach ($classes as $class) {
                if (is_a($class, DomainMessageInterface::class, true)) {
                    $r = new \ReflectionClass($class);
                    if ($r->isAbstract() || $r->isInterface()) {
                        continue;
                    }
                    // $typeName = $r->getMethod('getTypeName')->invoke(null);
                    $typeName = $class::getTypeName();
                    $map[$typeName] = $class;
                }
            }

            return $map;
        });

        return new DomainMessageClassMap($map);
    }
}