<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AdminController extends AbstractActionController
{
    /**
     *
     * Action that displays the main page
     *
     */

    public function indexAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Initialize the view
        $vm = new ViewModel(array(
            'companyList' => $companyService->getHiddenCompanyList(),
        ));

        return $vm;
    }

    /**
     *
     * Action that allows adding a company
     *
     */

    public function addCompanyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getCompanyForm();

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            if ($companyService->insertCompanyWithData($request->getPost())) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/default', 
                    array(
                        'action' => 'edit', 
                        'slugCompanyName' => $companyName
                    ), 
                    array(), 
                    false
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                array('action' => 'addCompany')
            )
        );

        // Initialize the view
        $vm = new ViewModel(array(
            'companyEditForm' => $companyForm,
        ));

        return $vm;
    }

    /**
     *
     * Action that allows adding a packet
     *
     */

    public function addPacketAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $packetForm = $companyService->getPacketForm();

        // Get parameter
        $companyName = $this->params('slugCompanyName');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {

            // Check if data is valid, and insert when it is
            if ($companyService->insertPacketForCompanySlugNameWithData($companyName,$request->getPost())){
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editCompany', 
                    array('slugCompanyName' => $companyName), 
                    array(), 
                    false
                );
            }

        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $packetForm->setAttribute(
            'action',
            $this->url()->fromRoute('admin_company/editCompany/addPacket',
            array('slugCompanyName' => $companyName))
        );

        // Initialize the view
        $vm = new ViewModel(array(
            'companyEditForm' => $packetForm,
        ));

        return $vm;
    }

    /**
     *
     * Action that allows adding a job
     *
     */
    public function addJobAction()
    {
        // Get useful stuf
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobForm();

        // Get parameters
        $companyName = $this->params('slugCompanyName');
        $packetId = $this->params('packetID');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {

            // Check if data is valid, and insert when it is
            if ($companyService->insertJobIntoPacketIDWithData($packetId, $request->getPost())) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editCompany/editPacket/editJob',
                    array(
                        'slugCompanyName' => $companyName,
                        'packetID' => $packetId,
                        'jobName' => $job->getName(), 
                    )
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPacket/addJob',
                array(
                    'slugCompanyName' => $companyName, 
                    'packetID' => $packetId
                )
            )
        );

        // Initialize the view
        $vm = new ViewModel(array(
            'companyEditForm' => $companyForm,
        ));

        return $vm;
    }

    /**
     *
     * Action that displays a form for editing a company
     *
     */
    public function editCompanyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getCompanyForm();

        // Get parameter
        $companyName = $this->params('slugCompanyName');

        // Get the specified company
        $companyList = $companyService->getEditableCompaniesWithSlugName($companyName);

        // If the company is not found, throw 404
        if (empty($companyList)) {
            $company = null;
            $this->getResponse()->setStatusCode(404);
            return; 
        }

        $company = $companyList[0];

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->saveCompanyWithData($company, $request->getPost());
        }

        // Initialize form
        $companyForm->bind($company);
        $companyForm->get('languages')->setValue($company->getArrayCopy()['languages']);
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                array(
                    'action' => 'editCompany',
                    'slugCompanyName' => $companyName, 
                )
            )
        );
        $jobs = $companyService->getJobsWithCompanySlugName($companyName);
        $vm = new ViewModel(array(
            'company' => $company,
            'companyEditForm' => $companyForm,
        ));

        return $vm;
    }

    /**
     *
     * Action that displays a form for editing a packet
     *
     */
    public function editPacketAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $packetForm = $companyService->getPacketForm();

        // Get the parameters
        $companyName = $this->params('slugCompanyName');
        $packetID = $this->params('packetID');

        // Get the specified packet (Assuming it is found)
        $packet = $companyService->getEditablePacket($packetID);

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->savePacketWithData($packet,$request->getPost());
            // TODO: possibly redirect to company
        }
        // TODO: display error page when packet is not found

        // Initialize form
        $packetForm->bind($packet);
        $packetForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPacket',
                array(
                    'packetID' => $packetID, 
                    'slugCompanyName' => $companyName, 
                )
            )
        );

        // Initialize the view
        $vm = new ViewModel(array(
            'packet' => $packet,
            'companyName' => $companyName,
            'packetEditForm' => $packetForm,
        ));

        return $vm;
    }

    /**
     *
     * Action that displays a form for editing a job
     *
     */
    public function editJobAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $jobForm = $companyService->getJobForm();
        
        // Get the parameters
        $packetID = $this->params('packetID');
        $companyName = $this->params('slugCompanyName');
        $jobName = $this->params('jobName');


        // Find the specified jobs
        $jobList = $companyService->getEditableJobsWithSlugName($companyName, $jobName);

        // Check the job is found. If not, throw 404
        if (empty($jobList)) {
            $company = null;
            $this->getResponse()->setStatusCode(404);
            return; 
        }

        $job = $jobList[0];

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->saveJobWithData($job, $request->getPost());
            // TODO: possibly redirect to packet
        }

        // Initialize the form
        $jobForm->bind($job);
        $jobForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPacket/editJob',
                array(
                    'slugCompanyName' => $companyName, 
                    'jobName' => $jobName,
                    'packetID' => $packetID,
                )
            )
        );

        // Initialize the view
        $vm = new ViewModel(array(
            'jobEditForm' => $jobForm,
        ));

        return $vm;
    }

    /**
     *
     * Extracted part of delete actions that checks if confirmation is given
     *
     */

    private function checkConfirmation($request)
    {
        $del = $request->getPost('del', 'No');
        if ($del === 'Yes') {
            return true;
        }
        return false;

    }

    /**
     *
     * Action that first asks for confirmation, and when given, deletes the company
     *
     */
    public function deleteCompanyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Get parameters
        $slugName = $this->params('slugCompanyName');

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {

            // Check for confirmation
            if ($this->checkConfirmation($request)) {
                $companyService->deleteCompaniesWithSlug($slugName);
            }

            return $this->redirect()->toRoute('admin_company');
        }

        // No data returned, so instead, ask for confirmation

        // Initialize the view
        $vm = new ViewModel(array(
            'companies' => $companyService->getEditableCompaniesWithSlugName($slugName),
            'translator' => $companyService->getTranslator(),
        ));
        return $vm;
    }

    /**
     *
     * Action that first asks for confirmation, and when given, deletes the Packet
     *
     */
    public function deletePacketAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Get parameters
        $packetID = $this->params('packetID');
        $companyName = $this->params('slugCompanyName');

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->checkConfirmation($request)) {
                $companyService->deletePacket($packetID);
            }
            return $this->redirect()->toRoute(
                'admin_company/editCompany', 
                array('slugCompanyName' => $companyName)
            );
        }

        // No data returned, so instead, ask for confirmation

        // Initialize the view
        $vm =  new ViewModel(array(
            'packet' => $companyService->getEditablePacket($packetID),
            'slugName' => $companyName,
            'translator' => $companyService->getTranslator(),
        ));

        return $vm;
    }

    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }
}
