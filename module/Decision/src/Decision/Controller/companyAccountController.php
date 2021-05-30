<?php

namespace Decision\Controller;

use Company\Service\Company as CompanyService;
use Doctrine\DBAL\Schema\View;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class companyaccountController extends AbstractActionController
{
    public function IndexAction()
    {
        if (!$this->getCompanyAccountService()->isAllowed('view')) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view this page')
            );
        }
        return new ViewModel();
    }


    public function dummyAction(){
        return new ViewModel();
    }


    public function profileAction() {
        return new ViewModel();
    }
    public function settingsAction() {
        return new ViewModel();
    }


    public function vacanciesAction(){

        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Get the parameters
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyName = $company->getName();
        $packageId = $company->getJobPackageId();

        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageId);
        $type = $package->getType();

        $jobs = $companyService;

        // Get form
        $packageForm = $companyService->getPackageForm($type);

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->savePackageByData($package, $request->getPost(), $request->getFiles())) {
                // TODO: possibly redirect to company
            }
        }
        // TODO: display error page when package is not found

        // Initialize form
        $packageForm->bind($package);
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'companyaccount/vacancies',
                [
                    'packageId' => $packageId,
                    'slugCompanyName' => $companyName,
                    'type' => $type,
                ]
            )
        );

        // Initialize the view
        return new ViewModel([
            'package' => $package,
            'companyName' => $companyName,
            'form' => $packageForm,
            'type' => $type,
        ]);
    }

    public function editVacancyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $jobForm = $companyService->getJobFormCompany();


        // Get the parameters
        $languageNeutralId = $this->params('languageNeutralJobId');

        // Find the specified jobs
        $jobs = $companyService->getEditableJobsByLanguageNeutralId($languageNeutralId);

        // Check the job is found. If not, throw 404
        if (empty($jobs)) {
            return $this->notFoundAction();
        }

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles();
            $post = $request->getPost();
            $jobDict = [];

            foreach ($jobs as $job) {
                $jobDict[$job->getLanguage()] = $job;
            }

            $companyService->saveJobData($languageNeutralId, $jobDict, $post, $files);
        }

        // Initialize the form
        $jobDict = [];
        foreach ($jobs as $job) {
            $jobDict[$job->getLanguage()] = $job;
        }
        $languages = array_keys($jobDict);
        $jobForm->setLanguages($languages);

        $labels = $jobs[0]->getLabels();

        $mapper = $companyService->getLabelMapper();
        $actualLabels = [];
        foreach ($labels as $label) {
            $actualLabel = $label->getLabel();
            $actualLabels[] = $mapper->siblingLabel($actualLabel, 'en');
            $actualLabels[] = $mapper->siblingLabel($actualLabel, 'nl');
        }


        // Handle incoming form data for central fields
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $x = 0;
            foreach ($jobs as $job) {
                $job->setSectors($companyService->getJobMapper()->findSectorsById($post['sectors'] + $x));
                $job->setCategory($companyService->getJobMapper()->findCategoryById($post['category'] +$x));
                $x++;
                $job->exchangeArray($post);
            }
            $companyService->saveJob();
        }


        $jobForm->setLabels($actualLabels);
        $jobForm->setData($jobs[0]->getArrayCopy());
        $jobForm->bind($jobDict);


        // Initialize the view
        return new ViewModel([
            'form' => $jobForm,
            'job' => $job,
            'languages' => $this->getLanguageDescriptions(),
        ]);

    }



    /**
     * Action that allows adding a job
     *
     */
    public function createVacancyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobFormCompany();


        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $packageId = $company->getJobPackageId();
        if($packageId == null) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You do not have a package to create vacancies.')
            );
        }
        // Handle incoming form results
        $request = $this->getRequest();


        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $job = $companyService->createJob(
                $packageId,
                $request->getPost(),
                $request->getFiles()
            );

            if ($job) {

                //Send approval email to admin
                $email = $this->getDecisionEmail();
                $email->sendApprovalMail($company);

                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'companyaccount/vacancies'
                );
            }
        }

        // Initialize the form
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'companyaccount/vacancies/createvacancy'
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'form' => $companyForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);

        return $vm;
    }

    private function getLanguageDescriptions()
    {
        $companyService = $this->getCompanyService();
        $languages = $companyService->getLanguages();
        $languageDictionary = [];
        foreach ($languages as $key) {
            $languageDictionary[$key] = $companyService->getLanguageDescription($key);
        }

        return $languageDictionary;
    }


    /**
     * Method that returns the service object for the company module.
     *
     * @return CompanyService
     */
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }

    /**
     * Get the company service.
     *
     * @return Decision\Service\CompanyAccount
     */
    public function getCompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyaccount');
    }

    /**
     * Method that returns the service object for the company module.
     *
     * @return DesicionEmail
     */
    protected function getDecisionEmail()
    {
        return $this->getServiceLocator()->get('decision_service_decisionEmail');
    }
}
