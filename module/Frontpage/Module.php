<?php

namespace Frontpage;

class Module
{
    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Get service configuration.
     *
     * @return array Service configuration
     */
    public function getServiceConfig()
    {
        return [
            'invokables' => [
                'frontpage_service_frontpage' => 'Frontpage\Service\Frontpage',
                'frontpage_service_page' => 'Frontpage\Service\Page',
                'frontpage_service_page' => 'Frontpage\Service\Page',
                'frontpage_service_poll' => 'Frontpage\Service\Poll'
            ],
            'factories' => [
                'frontpage_form_page' => function ($sm) {
                    $form = new \Frontpage\Form\Page(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator_page'));

                    return $form;
                },
                'frontpage_form_poll' => function ($sm) {
                    $form = new \Frontpage\Form\Poll(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator_poll'));

                    return $form;
                },
                'frontpage_form_poll_approval' => function ($sm) {
                    $form = new \Frontpage\Form\PollApproval(
                        $sm->get('translator')
                    );
                    $form->setHydrator($sm->get('frontpage_hydrator_poll'));

                    return $form;
                },
                'frontpage_hydrator_page' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('frontpage_doctrine_em'),
                        'Frontpage\Model\Page'
                    );
                },
                'frontpage_hydrator_poll' => function ($sm) {
                    return new \DoctrineModule\Stdlib\Hydrator\DoctrineObject(
                        $sm->get('frontpage_doctrine_em'),
                        'Frontpage\Model\Poll'
                    );
                },
                'frontpage_mapper_page' => function ($sm) {
                    return new Mapper\Page(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_mapper_poll' => function ($sm) {
                    return new Mapper\Poll(
                        $sm->get('frontpage_doctrine_em')
                    );
                },
                'frontpage_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    $acl->addResource('page');
                    $acl->addResource('poll');
                    $acl->addResource('poll_comment');

                    $acl->allow('user', 'poll', ['vote', 'request']);
                    $acl->allow('user', 'poll_comment', 'view');

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                // reused code from the eduction module
                'frontpage_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            ]
        ];
    }
}
