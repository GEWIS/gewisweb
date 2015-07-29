<?php

namespace Decision\Service;

use Application\Service\AbstractAclService;

/**
 * Decision service.
 */
class Decision extends AbstractAclService
{

    /**
     * Search for decisions.
     *
     * @param array|Traversable $data Search data
     *
     * @return array Search results
     */
    public function search($data)
    {
        $form = $this->getSearchDecisionForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        return array();
    }

    /**
     * Get the SearchDecision form.
     *
     * @return Decision\Form\SearchDecision
     */
    public function getSearchDecisionForm()
    {
        return $this->sm->get('decision_form_searchdecision');
    }

    /**
     * Get the decision mapper.
     *
     * @return Decision\Mapper\Decision
     */
    public function getDecisionMapper()
    {
        return $this->sm->get('decision_mapper_decision');
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'decision';
    }

    /**
     * Get the Acl.
     *
     * @return Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->sm->get('decision_acl');
    }
}
