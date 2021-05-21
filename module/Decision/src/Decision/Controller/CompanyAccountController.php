<?php

namespace Decision\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Form\Form as Form;
use Zend\Validator\File\IsImage;

class companyaccountController extends AbstractActionController
{


    public function indexAction()
    {
        $decisionService = $this->getServiceLocator()->get('decision_service_decision');
        $company = "TestA";

        return new ViewModel([
            //fetch the active vacancies of the logged in company
            'vacancies' => $this->getcompanyAccountService()->getActiveVacancies($company),
            'company' => $company
        ]);
    }

    public function banneruploadAction(){
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();
        $companyName = $company->getName();



        // Get Zend validator
        $image_validator = new IsImage();

        // Get form
        $packageForm = $companyService->getPackageForm('banner');

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles();
            $post = $request->getPost();
            $post['published'] = 0;

            // Check if the upload file is an image
//            if ($image_validator->isValid($files['banner'])) {
//                $image = $files['banner'];
//
//                // Check if the size of the image is 90x728
//                if ($this->checkImageSize($image, $packageForm)) {
//                    if ($this->checkValidDate($posts['startDate'], $posts['expirationDate'])){
//                        // TODO Check credits





//                        if ($companyService->insertPackageForCompanySlugNameByData(
//                            $companyName,
//                            $request->getPost(),
//                            $image,
//                            'banner'
//                        )) {
//
//                            //TODO: make redirect to page the banner is shown
//                            // Redirect to company page
//                            return $this->redirect()->toRoute(
//                                'companyaccount'
//                            );
//                        }
//                    }
//                }
//            } else {
//                echo "Is not image";
//            }
            if ($companyService->insertPackageForCompanySlugNameByData(
                $companyName,
                $post,
                $files['banner'],
                'banner'
            )) {
                $this->deductCredits($post);

                //TODO: make redirect to page the banner is shown
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
                'companyaccount/bannerupload'
            )
        );

        return new ViewModel([
            'form' => $packageForm
        ]);
    }

    public function deductCredits($post) {
        $companyService = $this->getCompanyService();
        $company = $this->getCompanyAccountService()->getCompany()->getCompanyAccount();

        $ban_start = new \DateTime($post['startDate']);
        $ban_end = new \DateTime($post['expirationDate']);
        $ban_days = $ban_end->diff($ban_start)->format("%a");
        //echo $ban_days." days have been selected";                   //testing $ban_days

        $ban_credits = $company->getBannerCredits();
        if ($ban_credits >= $ban_days ){

            //echo "Old Credits: ".$ban_credits." /// Ban_days: ".$ban_days." /// ";
            $ban_credits = $ban_credits - $ban_days;            //deduct banner credits based on days scheduled

            $company->setBannerCredits($ban_credits);           //set new credits
            $ban_credits = $company->getBannerCredits();
            //echo "set Credits:".$ban_credits."///";
            $companyService->saveCompany();

        } // else notify "Insufficient credit"
    }

    public function checkImageSize($image, $packageForm) {
        list($image_width, $image_height) = getimagesize($image['tmp_name']);

        if ($image_height != 90 ||
        $image_width != 728) {
            $wrongDimensionMessage = "The image you submitted does not have the right dimensions" .
                "The dimensions of your image are " . $image_height . " x " . $image_width . "\n" .
                "The dimensions of the image should be 90 x 728";
            return false;
        } else {
            return true;
        }

    }

    public function checkValidDate($startdate, $expdate) {
        $today = date("Y-m-d");
        if ($today > $startdate) {
            echo "startday should be valid";
            return false;
        } elseif ($startdate >= $expdate) {
            echo "startday should be before expiration day";
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

}
