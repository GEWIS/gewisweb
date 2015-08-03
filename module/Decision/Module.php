<?php
namespace Decision;


class Module
{

    /**
     * Get the autoloader configuration.
     *
     * @return array Autoloader config
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                )
            )
        );
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
        return array(
            'invokables' => array(
                'decision_service_organ' => 'Decision\Service\Organ',
                'decision_service_decision' => 'Decision\Service\Decision',
                'decision_service_member' => 'Decision\Service\Member'
            ),
            'factories' => array(
                'decision_mapper_member' => function ($sm) {
                    return new \Decision\Mapper\Member(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_organ' => function ($sm) {
                    return new \Decision\Mapper\Organ(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_meeting' => function ($sm) {
                    return new \Decision\Mapper\Meeting(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_mapper_decision' => function ($sm) {
                    return new \Decision\Mapper\Decision(
                        $sm->get('decision_doctrine_em')
                    );
                },
                'decision_form_searchdecision' => function ($sm) {
                    return new \Decision\Form\SearchDecision(
                        $sm->get('translator')
                    );
                },
                'decision_form_notes' => function ($sm) {
                    return new \Decision\Form\Notes(
                        $sm->get('translator'),
                        $sm->get('decision_mapper_meeting')
                    );
                },
                'decision_acl' => function ($sm) {
                    $acl = $sm->get('acl');

                    // add resources for this module
                    $acl->addResource('organ');
                    $acl->addResource('member');
                    $acl->addResource('decision');
                    $acl->addResource('meeting');

                    // users are allowed to view the organs
                    $acl->allow('guest', 'organ', 'list');
                    $acl->allow('user', 'organ', 'view');

                    // guests are allowed to view birthdays on the homepage
                    $acl->allow('guest', 'member', 'birthdays_today');

                    // users are allowed to view and search members
                    $acl->allow('user', 'member', array('view', 'search', 'birthdays'));
                    $acl->allow('user', 'member', array('view', 'view_self', 'search', 'birthdays'));

                    $acl->allow('user', 'decision', array('search', 'view_meeting', 'list_meetings'));

                    $acl->allow('user', 'meeting', array('view', 'view_notes'));

                    return $acl;
                },
                // fake 'alias' for entity manager, because doctrine uses an abstract factory
                // and aliases don't work with abstract factories
                'decision_doctrine_em' => function ($sm) {
                    return $sm->get('doctrine.entitymanager.orm_default');
                }
            )
        );
    }
}
