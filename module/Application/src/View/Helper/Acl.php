<?php

namespace Application\View\Helper;

use Application\Service\AbstractAclService;
use Interop\Container\ContainerInterface;
use Laminas\Permissions\Acl\Resource\ResourceInterface;
use Laminas\ServiceManager\Exception\InvalidArgumentException;
use Laminas\View\Helper\AbstractHelper;

class Acl extends AbstractHelper
{
    /**
     * Service locator.
     *
     * @var ContainerInterface
     */
    protected ContainerInterface $locator;

    /**
     * Acl.
     *
     * @var AbstractAclService
     */
    protected AbstractAclService $acl;

    /**
     *
     * @param ResourceInterface|string $resource
     * @param string $operation
     * @return bool
     */
    public function isAllowed($resource, string $operation)
    {
        return $this->acl->isAllowed($operation, $resource);
    }

    /**
     * Returns the Acl for a specific module.
     *
     * @param string $factory Acl factory to load
     *
     * @return Acl
     */
    public function __invoke(string $factory)
    {
        $this->acl = $this->getServiceLocator()->get($factory);
        if ($this->acl instanceof AbstractAclService) {
            return $this;
        } else {
            throw new InvalidArgumentException('Provided factory does not exist or does not return an Acl instance');
        }
    }

    /**
     * Get the service locator.
     *
     * @return ContainerInterface
     */
    protected function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Set the service locator.
     *
     * @param ContainerInterface $locator
     */
    public function setServiceLocator(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }
}
