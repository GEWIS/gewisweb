<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Form\Form as Form;

class companyaccountController extends AbstractActionController
{


    public function indexAction()
    {
        $decisionService = $this->getServiceLocator()->get('decision_service_decision');
        $company = "Phillips";

        return new ViewModel([
            //fetch the active vacancies of the logged in company
            'vacancies' => $this->getcompanyAccountService()->getActiveVacancies($company),
            'company' => $company
        ]);
    }

    public function banneruploadAction(){
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyName = "TestA";

        // Get form
        $packageForm = $companyService->getPackageForm('banner');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles();

            if ($companyService->insertPackageForCompanySlugNameByData(
                $companyName,
                $request->getPost(),
                $files['banner'],
                'banner'
            )) {

                // Redirect to company page
                return $this->redirect()->toRoute(
                    'companyaccount'
                );
            }
        }

        // Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/addPackage',
                ['slugCompanyName' => $companyName, 'type' => 'banner']
            )
        );

        return new ViewModel([
            'form' => $packageForm
        ]);
    }

    public function dummyAction(){
        return new ViewModel();
    }

    public function profileAction() {
        return new ViewModel();
    }


    public function test(){
        return "test";
    }

    public function settingsAction() {
        $company = "Phillips";
        $companyInfo = $this->getSettingsService()->getCompanyInfo($company);
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyInfo[0]->getId());

        return new ViewModel([
            'companyPackageInfo' => $companyPackageInfo,
            'companyInfo'  => $companyInfo,
            'settingsService' => $this->getSettingsService()
        ]);
    }



    public function vacanciesAction(){
        return new ViewModel();
    }

    public function editVacancyAction() {
        return new ViewModel();
    }

    /**
     * Action that allows adding a job
     *
     *
     */
    public function createVacancyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobFormCompany();

        // Get parameters
//        $companyName = $this->params('slugCompanyName');
//        $packageId = $this->params('packageId');
        $companyName = 'Phillips';
        $packageId = 2;


//        $company = $this->identity()->getMember();

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
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'companyAccount/vacancies'
                );
            }
        }

        // TODO: change redirect after company has been created.

        // Initialize the form
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPackage/addJob',
                [
                    'slugCompanyName' => $companyName,
                    'packageId' => $packageId
                ]
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
     * Method that returns the service object for the company module.
     *
     * @return Decision\Service\Settings
     */
    protected function getSettingsService()
    {
        return $this->getServiceLocator()->get('decision_service_settings');
    }

    /**
     * Get the CompanyAccount service.
     *
     * @return Decision\Service\CompanyAccount
     */
    public function getcompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyAccount');
    }

}
