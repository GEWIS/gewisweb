<?php
namespace Application\Service;

use Zend\Cache\Storage\Adapter\MemcachedOptions;
use Zend\Cache\Storage\Adapter\MemcachedResourceManager;
use Zend\Cache\StorageFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Session\SaveHandler\Cache;

class MemcachedStorageFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $memcachedResourceManager = new MemcachedResourceManager();
        $memcachedResourceManager->addServers('default', $serviceLocator->get('Config')['memcached_session']['servers']);
        $memcachedResourceManager->setLibOptions('default', $serviceLocator->get('Config')['memcached_session']['lib_options']);

        $memcachedOptions = new MemcachedOptions();
        $memcachedOptions->setResourceManager($memcachedResourceManager);

        $adapter = StorageFactory::adapterFactory('memcached', $memcachedOptions);

        return new Cache($adapter);
    }

}