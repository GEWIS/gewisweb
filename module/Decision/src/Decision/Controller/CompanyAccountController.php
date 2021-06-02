<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Form\Form as Form;
use Zend\Validator\File\IsImage;

class companyaccountController extends AbstractActionController
{
    public $MSG;

    public function indexAction()
    {
        return new ViewModel([
            //fetch the active vacancies of the logged in company
            'vacancies' => $this->getVacancies(),
        ]);
    }

    public function banneruploadAction(){
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        global $MSG;

        // Get form
        $packageForm = $companyService->getPackageForm('banner');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles();
            $post = $request->getPost();
            $post['published'] = 0;

            if ($this->bannerPostCorrect($post, $files)) {
                // Upload the banner to database and redirect to Companypanel
                if ($companyService->insertPackageForCompanySlugNameByData(
                    $company->getName(),
                    $post,
                    $files['banner'],
                    'banner'
                )) {
                    return $this->redirect()->toRoute(
                        'companyaccount'
                    );
                }
            } else {
                echo $this->function_alert($MSG);
                $packageForm->setData($this->resetInsertedDates($post));
            }
        }

        // Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'companyaccount/bannerupload'
            )
        );

        $email = $this->getDecisionEmail();
        $email->sendApprovalMail($company);

        return new ViewModel([
            'form' => $packageForm,
            'company' => $company
        ]);
    }

    public function bannerPostCorrect($post, $files) {
        global $MSG;
        // Get Zend validator
        $image_validator = new IsImage();
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        // Check if valid timespan is selected
        if (new \DateTime($post['expirationDate']) <= new \DateTime($post['startDate'])) {
            $MSG = "Please make sure the expirationdate is after the startingdate.";
            return false;
        }

        // Check if the upload file is an image
        if (!$image_validator->isValid($files['banner'])) {
            $MSG = "Please submit an image.";
            //$packageForm->setData($this->resetInsertedDates($post));
            return false;
        }

        // Check if the size of the image is 90x728
        if (!$this->checkImageSize($files['banner'])) {
            // TODO Implement cropping tool (Could)
            return false;
        }

        // Check if Company has enough credits and subtract them if so
        if (!$this->checkCredits($post, $company, $companyService, "banner")) {
            return false;
        }

        return true;
    }

    /**
     * Shows the highlighting page and processes the form when submit has been clicked
     *
     *
     */
    public function highlightAction(){
        global $MSG;
        //Get usefull stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        //Get package form of type highlight
        $packageForm = $companyService->getPackageForm('highlight');

        //Set the values for the selection element
        $packageForm->get('vacancy_id')->setValueOptions($this->getVacancyNames($this->getVacancies()));

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            //Set published to one, since a highlight does not need to be approved
            $post['published'] = 1;

            if ($this->checkCredits($post, $company, $companyService, "highlight")) {
                if ($companyService->insertPackageForCompanySlugNameByData(
                    $company->getName(),
                    $post,
                    NULL, //There are no files to be passed
                    'highlight'
                )) {
                    return $this->redirect()->toRoute(
                        'companyaccount'
                    );
                }
            } else {
                echo $this->function_alert($MSG);
            }
        }

        //Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'companyaccount/highlight'
            )
        );

        return new ViewModel([
            'form' => $packageForm,
            'company' => $company
        ]);
    }

    /**
     * Gets all active vacancies for a certain company
     *
     *
     */
    public function getVacancies() {
        $companyService = $this->getCompanyService();

        //Get current language
        $translator = $companyService->getTranslator();
        $locale = $translator->getLocale();

        //Obtain the id of the logged in company
        $companyId = $this->getCompanyAccountService()->getCompany()->getCompanyAccount()->getId();

        //obtain company package information
        $companyPackageInfo = $this->getcompanyAccountService()->getCompanyPackageInfo($companyId);

        return $this->getcompanyAccountService()->getActiveVacancies($companyPackageInfo[0]->getId(), $locale);
    }

    /**
     * Gets an array with the names from all vacancies in a vacancy object
     * where the location in the array is the vacancy id
     *
     *
     */
    public function getVacancyNames($vacancy_objects) {
        $vacancyNames = [];

        foreach ($vacancy_objects as &$vacancy) {
            $vacancyNames[$vacancy->getId()] = $vacancy->getName();
        }
        return $vacancyNames;
    }

    public function deductCredits($company, $companyService, $days_scheduled, $credits_owned, $type) {
        $credits_owned = $credits_owned - $days_scheduled;            //deduct banner credits based on days scheduled

        if ($type === "banner"){
            $company->setBannerCredits($credits_owned);
        } elseif ($type === "highlight"){
            $company->setHighlightCredits($credits_owned);
        }
        $companyService->saveCompany();
    }

    public function checkCredits($post, $company, $companyService, $type) {
        global $MSG;
        $start_date = new \DateTime($post['startDate']);
        $end_date = new \DateTime($post['expirationDate']);
        $days_scheduled = $end_date->diff($start_date)->format("%a");

        if ($type === "banner"){
            $credits_owned = $company->getBannerCredits();
        } elseif ($type === "highlight"){
            $credits_owned = $company->getHighlightCredits();
        }

        if ($credits_owned >= $days_scheduled ){
            $this->deductCredits($company, $companyService, $days_scheduled, $credits_owned, $type);
            return true;
        }

        $MSG = "The amount of highlighting days needed is: " . $days_scheduled . ". The amount you have is: " . $credits_owned . ".";
        return false;
    }

    public function function_alert($msg){
        echo "<script type='text/javascript'>alert('$msg');</script>";
    }

    public function resetInsertedDates($post) {
        $insertedDates = [];
        $insertedDates['startDate'] = $post['startDate'];
        $insertedDates['expirationDate'] = $post['expirationDate'];
        return $insertedDates;
    }

    public function checkAndDeductCredits($post, $company, $companyService) {
        global $MSG;
        $ban_start = new \DateTime($post['startDate']);
        $ban_end = new \DateTime($post['expirationDate']);
        $ban_days = $ban_end->diff($ban_start)->format("%a");

        $ban_credits = $company->getBannerCredits();
        if ($ban_credits >= $ban_days ){
            $ban_credits = $ban_credits - $ban_days;            //deduct banner credits based on days scheduled

            $company->setBannerCredits($ban_credits);           //set new credits
            $ban_credits = $company->getBannerCredits();
            $companyService->saveCompany();
            return true;
        }
        $MSG = "The amount of credits needed is: " . $ban_days . ". The amount you have is: " . $ban_credits . ".";
        return false;
    }

    public function checkImageSize($image) {
        global $MSG;
        list($image_width, $image_height) = getimagesize($image['tmp_name']);

        if ($image_height != 90 ||
        $image_width != 728) {
            $MSG = "The image you submitted does not have the right dimensions. " .
                "The dimensions of your image are " . $image_height . " x " . $image_width .
                ". The dimensions of the image should be 90 x 728.";
            return false;
        }
        return true;
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
        $company = "COmpany";
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

    /**
     * Method that returns the service object for the company module.
     *
     * @return DesicionEmail
     */
    protected function getDecisionEmail()
    {
        return $this->getServiceLocator()->get('decision_service_decisionEmail');
    }

    /**
     * Get the CompanyAccount mapper.
     *
     * @return \Decision\Mapper\companyAccount
     */
    public function getCompanyAccountMapper()
    {
        return $this->getServiceLocator()->get('decision_mapper_companyAccount');
    }
}
