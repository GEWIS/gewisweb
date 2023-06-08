<?php

declare(strict_types=1);

namespace Application\View\Helper;

use Application\Service\AbstractAclService;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\View\Helper\AbstractHelper;
use Psr\Container\ContainerInterface;

class Acl extends AbstractHelper
{
    /**
     * Service locator.
     */
    protected ContainerInterface $locator;

    /**
     * Acl.
     */
    protected AbstractAclService $acl;

    public function isAllowed(
        ResourceInterface|string $resource,
        string $operation,
    ): bool {
        return $this->acl->isAllowed($operation, $resource);
    }

    /**
     * Returns the Acl for a specific module.
     *
     * @param string $factory Acl factory to load
     *
     * @return Acl
     */
    public function __invoke(string $factory): self
    {
        $this->acl = $this->getServiceLocator()->get($factory);

        if ($this->acl instanceof AbstractAclService) {
            return $this;
        }

        throw new InvalidArgumentException('Provided factory does not exist or does not return an Acl instance');
    }

    /**
     * Get the service locator.
     */
    protected function getServiceLocator(): ContainerInterface
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     */
    public function setServiceLocator(ContainerInterface $locator): void
    {
        $this->locator = $locator;
    }
}
