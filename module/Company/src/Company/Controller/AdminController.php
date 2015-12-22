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
        $vm = new ViewModel([
            'companyList' => $companyService->getHiddenCompanyList(),
        ]);

        return $vm;
    }

    /**
     * Action that allows adding a company
     *
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
            if ($companyService->insertCompanyByData($request->getPost())) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/default', 
                    [
                        'action' => 'edit', 
                        'slugCompanyName' => $companyName
                    ],
                    [],
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
                ['action' => 'addCompany']
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'companyEditForm' => $companyForm,
        ]);

        return $vm;
    }

    /**
     * Action that allows adding a package
     *
     *
     */
    public function addPackageAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $packageForm = $companyService->getPackageForm();

        // Get parameter
        $companyName = $this->params('slugCompanyName');
        $type = $this->params('type');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {

            // Check if data is valid, and insert when it is
            if ($companyService->insertPackageForCompanySlugNameByData($companyName, $request->getPost(), $type)){
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editCompany', 
                    ['slugCompanyName' => $companyName],
                    [],
                    false
                );
            }

        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/addPackage',
                ['slugCompanyName' => $companyName, 'type' => $type]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'companyEditForm' => $packageForm,
        ]);

        return $vm;
    }

    /**
     * Action that allows adding a job
     *
     *
     */
    public function addJobAction()
    {
        // Get useful stuf
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobForm();

        // Get parameters
        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageID');
        echo($packageId);

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {

            // Check if data is valid, and insert when it is
            $job = $companyService->insertJobIntoPackageIDByData($packageId, $request->getPost());
            if (!is_null($job)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editCompany/editPackage/editJob',
                    [
                        'slugCompanyName' => $companyName,
                        'packageID' => $packageId,
                        'jobName' => $job->getName(),
                    ]
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPackage/addJob',
                [
                    'slugCompanyName' => $companyName, 
                    'packageID' => $packageId
                ]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'companyEditForm' => $companyForm,
        ]);

        return $vm;
    }

    /**
     * Action that displays a form for editing a company
     *
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
        $companyList = $companyService->getEditableCompaniesBySlugName($companyName);

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
            $companyService->saveCompanyByData($company, $request->getPost());
        }

        // Initialize form
        $companyForm->bind($company);
        $companyForm->get('languages')->setValue($company->getArrayCopy()['languages']);
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                [
                    'action' => 'editCompany',
                    'slugCompanyName' => $companyName,
                ]
            )
        );
        $vm = new ViewModel([
            'company' => $company,
            'companyEditForm' => $companyForm,
        ]);

        return $vm;
    }

    /**
     * Action that displays a form for editing a package
     *
     *
     */
    public function editPackageAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $packageForm = $companyService->getPackageForm();

        // Get the parameters
        $companyName = $this->params('slugCompanyName');
        $packageID = $this->params('packageID');

        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageID);

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->savePackageByData($package,$request->getPost());
            // TODO: possibly redirect to company
        }
        // TODO: display error page when package is not found

        // Initialize form
        $packageForm->bind($package);
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPackage',
                [
                    'packageID' => $packageID, 
                    'slugCompanyName' => $companyName,
                ]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'package' => $package,
            'companyName' => $companyName,
            'packageEditForm' => $packageForm,
        ]);

        return $vm;
    }

    /**
     * Action that displays a form for editing a job
     *
     *
     */
    public function editJobAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $jobForm = $companyService->getJobForm();
        
        // Get the parameters
        $packageID = $this->params('packageID');
        $companyName = $this->params('slugCompanyName');
        $jobName = $this->params('jobName');


        // Find the specified jobs
        $jobList = $companyService->getEditableJobsBySlugName($companyName, $jobName);

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
            $companyService->saveJobByData($job, $request->getPost());
            // TODO: possibly redirect to package
        }

        // Initialize the form
        $jobForm->bind($job);
        $jobForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPackage/editJob',
                [
                    'slugCompanyName' => $companyName, 
                    'jobName' => $jobName,
                    'packageID' => $packageID,
                ]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'jobEditForm' => $jobForm,
        ]);

        return $vm;
    }

    /**
     * Extracted part of delete actions that checks if confirmation is given
     *
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
     * Action that first asks for confirmation, and when given, deletes the company
     *
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
                $companyService->deleteCompaniesBySlug($slugName);
            }

            return $this->redirect()->toRoute('admin_company');
        }

        // No data returned, so instead, ask for confirmation

        // Initialize the view
        $vm = new ViewModel([
            'companies' => $companyService->getEditableCompaniesBySlugName($slugName),
            'translator' => $companyService->getTranslator(),
        ]);
        return $vm;
    }

    /**
     * Action that first asks for confirmation, and when given, deletes the Package
     *
     *
     */
    public function deletePackageAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Get parameters
        $packageID = $this->params('packageID');
        $companyName = $this->params('slugCompanyName');

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($this->checkConfirmation($request)) {
                $companyService->deletePackage($packageID);
            }
            return $this->redirect()->toRoute(
                'admin_company/editCompany', 
                ['slugCompanyName' => $companyName]
            );
        }

        // No data returned, so instead, ask for confirmation

        // Initialize the view
        $vm =  new ViewModel([
            'package' => $companyService->getEditablePackage($packageID),
            'slugName' => $companyName,
            'translator' => $companyService->getTranslator(),
        ]);

        return $vm;
    }

    /**
     * Method that returns the service object for the company module.
     *
     *
     */
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }
}
