<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use DateTime;
use Zend\Validator\File\IsImage;

class CompanyAccountController extends AbstractActionController
{

    public function IndexAction()
    {
        if (!$this->getCompanyAccountService()->isAllowed('view')) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view this page')
            );
        }
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyId = $company->getId();
        //obtain company package information
        $companyInfo = $this->getSettingsService()->getCompanyInfo($companyId);
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        // Get local language
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();
        $locale = $translator->getLocale();

        return new ViewModel([
            //fetch the active vacancies of the logged in company
            'vacancies' => $this->getcompanyAccountService()->getActiveVacancies($companyPackageInfo[0]->getID(), $locale),
            'companyPackageInfo' => $companyPackageInfo,
            'companyInfo'  => $companyInfo
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
                ))
                $email = $this->getDecisionEmail();
                $email->sendApprovalMail($company);
                {
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
        $packageForm->get('vacancy_id')->setValueOptions($this->getVacancyNames($this->getHighlightableVacancies($company->getId())));

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            //Set published to one, since a highlight does not need to be approved
            $post['published'] = 1;

            if ($this->highlightPostCorrect($post)) {
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
                $packageForm->setData($this->resetInsertedDates($post));
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


    public function highlightPostCorrect($post) {
        global $MSG;
        //Get usefull stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        // Check if valid timespan is selected
        if (new \DateTime($post['expirationDate']) <= new \DateTime($post['startDate'])) {
            $MSG = "Please make sure the expirationdate is after the startingdate.";
            return false;
        }

        //Check if a company does not already have three highlights
        if ($this->getCompanyService()->getNumberOfHighlights($company->getId()) >= 3) {
            $MSG = "Unfortunately you can place at most 3 highlights, which you already have";
            return false;
        }

        // Check if Company has enough credits and subtract them if so
        if (!$this->checkCredits($post, $company, $companyService, "highlight")) {
            return false;
        }

        return true;
    }

    /**
     * Gets all active vacancies for a certain company
     *
     * @return all active vacancies for a certain company
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
     * Gets all highlightable vacancies for a certain company
     * A vacancy is highlightable if
     * - It is active
     * - No other vacancies in the same category have been highlighted
     *
     * @return all highlightable vacancies for a certain company
     */
    public function getHighlightableVacancies($companyId) {
        $companyService = $this->getCompanyService();

        //Get current language
        $translator = $companyService->getTranslator();
        $locale = $translator->getLocale();

        //Find the vacancies which are not in a category that has the same languageNeurtalId as already highlighted vacancies
        return $this->getCompanyService()->getHighlightableVacancies($companyId, $locale);
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

    /**
     * Action that displays a form for editing a company
     *
     *
     */
    public function profileAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getCompanyForm();

        // get useful company info
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companySlugName = $company->getSlugName();
        $companyName = $company->getName();

        // Get the specified company
        $companyList = $companyService->getEditableCompaniesBySlugName($companySlugName);
        // If the company is not found, throw 404
        if (empty($companyList)) {
            $company = null;
            return $this->notFoundAction();
        }

        $company = $companyList[0];

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {

            $post = $request->getPost();
            // Save the company
            $companyService->saveCompanyByData(
                $company,
                $post,
                $request->getFiles()
            );

            $company->setSlugName($companySlugName);
            $company->setName($companyName);
            $companyService->saveCompany();

            return $this->redirect()->toRoute(
                'companyaccount/profile',
                [
                    'action' => 'edit',
                    'slugCompanyName' => $companyName,
                ],
                [],
                false
            );

        }

        // Initialize form
        $companyForm->setData($company->getArrayCopy());
        $companyForm->get('languages')->setValue($company->getArrayCopy()['languages']);
        $companyForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'companyaccount/profile',
                [
                    'action' => 'editCompany',
                    'slugCompanyName' => $companyName,
                ]
            )
        );

        $email = $this->getDecisionEmail();
        $email->sendApprovalMail($company);

        return new ViewModel([
            'company' => $company,
            'form' => $companyForm,
        ]);
    }

    public function settingsAction() {
        //Obtain company and company package information
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyId = $company->getId();
        $companyInfo = $this->getSettingsService()->getCompanyInfo($companyId);
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        return new ViewModel([
            'companyPackageInfo' => $companyPackageInfo,
            'companyInfo'  => $companyInfo,
            'settingsService' => $this->getSettingsService()
        ]);
    }

    public function vacanciesAction(){

        // Get useful stuff
        $companyService = $this->getCompanyService();

        // Get the parameters
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyName = $company->getName();
        $packageId = $company->getJobPackageId();
        $companyId = $company->getId();
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        if ($packageId == null) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need a vacancy package to manage your vacancies.')
            );
        }

        $validJobPackage = false;
        $now = new DateTime();
        foreach($companyPackageInfo as $package) {
            if ($package->getType() == "job" && !$package->isExpired($now)) {
                $validJobPackage = true;
            }
        }

        if (!$validJobPackage) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('Your vacancy package has expired, please contact an administrator if you wish to extend your vacancy package.')
            );
        }


        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageId);
        $type = $package->getType();


        // Get form
        $packageForm = $companyService->getPackageForm($type);

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->savePackageByData($package, $request->getPost(), $request->getFiles())) {

            }
        }


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

        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $packageId = $company->getJobPackageId();
        $companyId = $company->getId();
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        if ($packageId == null) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need a vacancy package to manage your vacancies.')
            );
        }

        $validJobPackage = false;
        $now = new DateTime();
        foreach($companyPackageInfo as $package) {
            if ($package->getType() == "job" && !$package->isExpired($now)) {
                $validJobPackage = true;
            }
        }

        if (!$validJobPackage) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('Your vacancy package has expired, please contact an administrator if you wish to extend your vacancy package.')
            );
        }


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
        $companyId = $company->getId();
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        if ($packageId == null) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You need a vacancy package to manage your vacancies.')
            );
        }

        $validJobPackage = false;
        $now = new DateTime();
        foreach($companyPackageInfo as $package) {
            if ($package->getType() == "job" && !$package->isExpired($now)) {
                $validJobPackage = true;
            }
        }

        if (!$validJobPackage) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('Your vacancy package has expired, please contact an administrator if you wish to extend your vacancy package.')
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
     * Method that returns the service object for the company module.
     *
     * @return Decision\Service\Settings
     */
    protected function getSettingsService()
    {
        return $this->getServiceLocator()->get('decision_service_settings');
    }

    /**
     * Get the CompanAccount service.
     *
     * @return Decision\Service\CompanyAccount
     */
    public function getCompanyAccountService()
    {
        return $this->getServiceLocator()->get('decision_service_companyAccount');
    }


    /**
     * Method that returns the service object for the company module.
     *
     * @return DecisionEmail
     */
    protected function getDecisionEmail()
    {
        return $this->getServiceLocator()->get('decision_service_decisionEmail');
    }
}
