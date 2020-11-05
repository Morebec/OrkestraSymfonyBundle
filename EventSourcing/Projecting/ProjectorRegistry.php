<?php


namespace Morebec\OrkestraSymfonyBundle\EventSourcing\Projecting;

use Morebec\Orkestra\EventSourcing\Projecting\ProjectorInterface;

/**
 * Simple service containing all the projectors to easily retrieve them in Console Commands.
 */
class ProjectorRegistry
{
    /**
     * @var ProjectorInterface[]
     */
    private $projectors;

    public function __construct(iterable $projectors)
    {
        $this->projectors = [];

        foreach ($projectors as $projector) {
            $this->addProjector($projector);
        }
    }

    /**
     * Returns a projector by its type name or null if it does not exists.
     * @param string $projectorTypeName
     * @return ProjectorInterface|null
     */
    public function getProjectorByTypeName(string $projectorTypeName): ?ProjectorInterface
    {
        if(!array_key_exists($projectorTypeName, $this->projectors)) {
            return null;
        }

        return $this->projectors[$projectorTypeName];
    }

    /**
     * Returns all the projectors.
     * @return array
     */
    public function getAll(): array
    {
        return array_values($this->projectors);
    }

    /**
     * Adds a projector to this registry.
     * @param ProjectorInterface $projector
     */
    private function addProjector(ProjectorInterface $projector): void
    {
        $this->projectors[$projector::getTypeName()] = $projector;
    }
}