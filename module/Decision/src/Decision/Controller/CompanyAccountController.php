<?php

namespace Decision\Controller;

use Company\Service\Approval;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use DateTime;
use Zend\Validator\File\IsImage;
use Company\Form\EditPackage as EditPackageForm;

class CompanyAccountController extends AbstractActionController
{

    public function IndexAction()
    {
        //Evaluate permissions
        if (!$this->getCompanyAccountService()->isAllowed('view')) {
            $translator = $this->getCompanyAccountService()->getTranslator();
            throw new \User\Permissions\NotAllowedException(
                $translator->translate('You are not allowed to view this page')
            );
        }
        //Get CompanyAccount information
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyId = $company->getId();

        //Obtain company package information
        $companyInfo = $this->getSettingsService()->getCompanyInfo($companyId);
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        // Get local language
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();
        $locale = $translator->getLocale();

        //Obtain vacancies to display in feed
        $vacancies = empty($companyPackageInfo) ? [] : $this->getcompanyAccountService()->getActiveVacancies($companyPackageInfo[0]->getID(), $locale);

        $approved = [];
        foreach ($vacancies as $vacancy) {
            $approved[$vacancy->getId()] = $this->getApprovalService()->getApprovedByVacancyId($vacancy->getId());
        }

        foreach($companyPackageInfo as $info){
            if(!is_null($info->getContractNumber())){
                $companyPackageInfo = $info;
                break;
            }
        }

        return new ViewModel([
            'vacancies' => $vacancies,
            'companyPackageInfo' => [$companyPackageInfo],
            'companyInfo'  => $companyInfo,
            'approved' => $approved
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

            if ($this->bannerPostCorrect($post, $files, $packageForm)) {
                // Upload the banner to database and redirect to Companypanel
                if ($companyService->insertPackageForCompanySlugNameByData(
                    $company->getSlugName(),
                    $post,
                    $files['banner'],
                    'banner',
                    True
                )){
                    $email = $this->getDecisionEmail();
                    $email->sendApprovalMail($company);
                    return $this->redirect()->toRoute(
                        'companyaccount'
                    );
                }

            } else {
                //echo $this->function_alert($MSG);
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


    public function bannerPostCorrect($post, $files, &$packageForm) {
        // Get Zend validator
        $image_validator = new IsImage();
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        if (!$this->checkDates($post, $packageForm)) {
            return false;
        }

        // Check if the upload file is an image
        if (!$image_validator->isValid($files['banner'])) {
            $packageForm->setError(EditPackageForm::INVALID_IMAGE_FILE, $companyService->getTranslator());
            return false;
        }

        list($image_width, $image_height) = getimagesize($files['banner']['tmp_name']);
        if ($image_height != 90 || $image_width != 728) {
            $packageForm->setError(EditPackageForm::IMAGE_WRONG_SIZE, $companyService->getTranslator());
            // TODO Implement cropping tool (Could)
            return false;
        }

        // Check if Company has enough credits and subtract them if so
        if (!$this->checkCredits($post, $company, $companyService, "banner")) {
            $packageForm->setError(EditPackageForm::NOT_ENOUGH_CREDITS_BANNER, $companyService->getTranslator());
            return false;
        }

        return true;
    }

    public function deleteHighlightAction() {
        //Call function to delete
        $this->getCompanyService()->getHighlightPackageMapper()->delete($this->params('packageId'));

        //Redirect to highlight page
        return $this->redirect()->toRoute(
            'companyaccount/highlight'
        );
    }

    public function editHighlightAction() {
        $companyService = $this->getCompanyService();

        //Get package form of type highlight
        $packageForm = $companyService->getPackageForm('highlight');
        // Get the specified package (Assuming it is found)
        $highlight = $companyService->getEditablePackage($this->params('packageId'));
        // Initialize form
        $packageForm->bind($highlight);

        //Set the selectable values for the selection element
        $packageForm->get('vacancy_id')->setValueOptions(
            $this->getCompanyService()->getEditVacancyNames($highlight->getCompany()->getId(),
                $highlight->getVacancy()->getCategory()->getLanguageNeutralId()));
        //Set the current highlighted vacancy as selected
        $packageForm->get('vacancy_id')->setValue($highlight->getVacancy()->getId());

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();

            //Set published to one, since a highlight does not need to be approved
            $post['published'] = $highlight->isPublished();
            $post['startDate'] = $highlight->getStartingDate()->format('Y-m-d') ;
            $post['expirationDate'] = $highlight->getExpirationDate()->format('Y-m-d') ;

            if ($companyService->savePackageByData($highlight, $post, null
            )) {

                return $this->redirect()->toRoute(
                    'companyaccount/highlight'
                );
            }
        }

        //Initialize the form
        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute("companyaccount/highlight/edit",
                [
                    "packageId" => $highlight->getId(),
                ]
            )
        );

        return new ViewModel([
            'form' => $packageForm
        ]);
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

        //Get current language
        $translator = $companyService->getTranslator();
        $lang = $translator->getLocale();

        //Get package form of type highlight
        $packageForm = $companyService->getPackageForm('highlight');

        //Set the values for the selection element
        $packageForm->get('vacancy_id')->setValueOptions($this->getCompanyService()->getVacancyNames($company->getId()));

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            //Set published to one, since a highlight does not need to be approved
            $post['published'] = 1;

            if ($this->highlightPostCorrect($post, $packageForm)) {
                if ($companyService->insertPackageForCompanySlugNameByData(
                    $company->getSlugName(),
                    $post,
                    NULL, //There are no files to be passed
                    'highlight'
                )) {
                    return $this->redirect()->toRoute(
                        'companyaccount/highlight'
                    );
                }
            } else {
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

        $currentHighlights = $this->getCompanyService()->getCurrentHighlights($company->getId(), $lang);

        return new ViewModel([
            'form' => $packageForm,
            'company' => $company,
            'currentHighlights' => $currentHighlights
        ]);
    }


    public function highlightPostCorrect($post, &$packageForm) {
        //Get usefull stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        if (!$this->checkDates($post, $packageForm)) {
            return false;
        }

        //Check if a company does not already have three highlights
        if ($this->getCompanyService()->getNumberOfHighlightsPerCompany($company->getId()) >= 3) {
            $packageForm->setError(EditPackageForm::COMPANY_HAS_THREE_HIGHLIGHTS, $companyService->getTranslator());
            return false;
        }

        //Check if there are not already three highlights in a certain category
        if ($this->getCompanyService()->getNumberOfHighlightsPerCategory($post['vacancy_id']) >= 3) {
            $packageForm->setError(EditPackageForm::ALREADY_THREE_HIGHLIGHTS_IN_CATEGORY, $companyService->getTranslator());
            return false;
        }

        // Check if Company has enough credits and subtract them if so
        if (!$this->checkCredits($post, $company, $companyService, "highlight")) {
            $packageForm->setError(EditPackageForm::NOT_ENOUGH_CREDITS_HIGHLIGHT, $companyService->getTranslator());
            return false;
        }

        return true;
    }

    public function checkDates($post, &$packageForm) {
        //Get usefull stuff
        $companyService = $this->getCompanyService();
        $today = date("Y-m-d");

        // Check if valid timespan is selected
        if ($post['expirationDate'] <= $post['startDate']) {
            $packageForm->setError(EditPackageForm::EXPIRATIONDATE_AFTER_STARTDATE, $companyService->getTranslator());
            return false;
        }

        // Check if valid timespan is selected
        if ($post['startDate'] <= $today) {
            $packageForm->setError(EditPackageForm::START_DATE_IN_PAST, $companyService->getTranslator());
            return false;
        }

        // Check if valid timespan is selected
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyId = $company->getId();
        $companyPackageInfo = $this->getSettingsService()->getCompanyPackageInfo($companyId);

        foreach($companyPackageInfo as $info){
            if(!is_null($info->getContractNumber())){
                $companyPackageInfo = [$info];
                break;
            }
        }


        $date = $companyPackageInfo[0]->getExpirationDate();

        if ($post['expirationDate'] >= $date) {
            $packageForm->setError(EditPackageForm::EXPIRATIONDATE_AFTER_PACKAGEDATE, $companyService->getTranslator());
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
     * Reduces a companies banner/highlight credits equivalent to
     *  how many banner/highlight days have been scheduled
     * @param $company              The company class
     * @param $companyService       The company service class
     * @param $days_scheduled       The number of days that a banner/highlight have been scheduled
     * @param $credits_owned        The number of credits owned
     * @param $type                 Type represents whether a "banner" or "highlight" was scheduled
     */
    public function deductCredits($company, $companyService, $days_scheduled, $credits_owned, $type) {
        $credits_owned = $credits_owned - $days_scheduled;  //deduct banner credits based on days scheduled
        if ($type === "banner"){
            $company->setBannerCredits($credits_owned);
        } elseif ($type === "highlight"){
            $company->setHighlightCredits($credits_owned);
        }
        $companyService->saveCompany();
    }

    /**
     * @param $post                 The submitted banner or highlight request form
     * @param $company              The company class
     * @param $companyService       The company service class
     * @param $type                 Type represents whether a "banner" or "highlight" was scheduled
     * @return bool                 Whether the function was able to invoke deductCredits() (True) or not (False)
     */
    public function checkCredits($post, $company, $companyService, $type) {
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
            $companyService->saveCompanyApprovalByData(
                $company,
                $post,
                $request->getFiles(),
                $company->getArrayCopy()['en_logo'],
                $company->getArrayCopy()['nl_logo']
            );

            $email = $this->getDecisionEmail();
            $email->sendApprovalMail($company);

            $company->setSlugName($companySlugName);
            $company->setName($companyName);
//            $companyService->saveCompany();

            sleep(5);
            return $this->redirect()->toRoute(
                'companyaccount/index',
                [
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

        //Select correct company package
        foreach($companyPackageInfo as $info){
            if(!is_null($info->getContractNumber())){
                $companyPackageInfo = $info;
                break;
            }
        }

        return new ViewModel([
            'companyPackageInfo' => [$companyPackageInfo],
            'companyInfo'  => $companyInfo,
            'settingsService' => $this->getSettingsService()
        ]);
    }

    /**
     * Generate the vacancies overview page
     *
     * @return ViewModel
     */
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

//            $companyService->saveJobData($languageNeutralId, $jobDict, $post, $files);
            $companyService->createJobApproval($packageId, $post, $files, $languageNeutralId);

            //Send approval email to admin
            $email = $this->getDecisionEmail();
            $email->sendApprovalMail($company);
        }

        // Initialize the form
        $jobDict = [];
        foreach ($jobs as $job) {
            $jobDict[$job->getLanguage()] = $job;
        }
        $languages = array_keys($jobDict);
        $jobForm->setLanguages($languages);

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
//            $companyService->saveJob();
        }

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
     * Switch a vacancy between active and inactive status
     *
     * @return array|\Zend\Http\Response
     */
    public function switchActiveAction() {

        $companyService = $this->getCompanyService();
        $languageNeutralId = $this->params('languageNeutralJobId');

        // Find the specified jobs
        $jobs = $companyService->getEditableJobsByLanguageNeutralId($languageNeutralId);

        // Check the job is found. If not, throw 404
        if (empty($jobs)) {
            return $this->notFoundAction();
        }

        foreach($jobs as $job) {
            $job->setActive($job->getActive() ? 0: 1);
            $companyService->getJobMapper()->persist($job);
            $companyService->getJobMapper()->save();
        }

        return $this->redirect()->toRoute(
            'companyaccount/vacancies'
        );
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
            $job = $companyService->createJobApproval(
                $packageId,
                $request->getPost(),
                $request->getFiles(),
                ""
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

    /**
     * Method that returns the service object for the approval module.
     *
     * @return Approval
     */
    protected function getApprovalService()
    {
        return $this->getServiceLocator()->get('company_service_approval');
    }
}
