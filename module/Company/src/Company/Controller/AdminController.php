<?php

namespace Company\Controller;

use DateInterval;
use DateTime;
use Zend\Mvc\Controller\AbstractActionController;
use Company\Service\Company as CompanyService;

use Company\Mapper\Company as CompanyMapper;
use Company\Model\Company as CompanyModel;
use Decision\Service\DecisionEmail as Email;
use Decision\Controller\CompanyAccountController as CompanyAccountController;
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
        return new ViewModel([
            'companyList' => $companyService->getHiddenCompanyList(),
            'categoryList' => $companyService->getCategoryList(false),
            'labelList' => $companyService->getLabelList(false),
            'translator' => $companyService->getTranslator(),
            'packageFuture' => $companyService->getPackageChangeEvents((new DateTime())->add(
                new DateInterval("P1M")
            )),
        ]);
    }

    public function approvalPageAction(){
        $pendingApprovals = $this->getApprovalService()->getPendingApprovals();
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();

        // Filter out vacancy approvals with non-locale languages
        $singleLanguageApprovals = [];
        foreach ($pendingApprovals as $approval) {
            if ($approval->getType() == "vacancy") {
                if ($approval->getVacancyApproval()->getLanguage() == $translator->getLocale()) {
                    array_push($singleLanguageApprovals, $approval);
                }
            } else {
                array_push($singleLanguageApprovals, $approval);
            }
        }
        // Initialize the view
        return new ViewModel([
            'pendingApprovals' => $singleLanguageApprovals
        ]);
    }


    public function approvalVacancyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $approvalService = $this->getApprovalService();
        $jobForm = $companyService->getJobFormCompany();

        // Get the parameters
        // TODO: make sure this doesn't need to be called 'slugCompanyName'
        $languageNeutralId = $this->params('slugCompanyName');


        // Find the specified jobs
        $jobs = $companyService->getEditableJobsByLanguageNeutralId($languageNeutralId);
        $vacancyApprovals = $approvalService->getEditableVacanciesByLanguageNeutralId($languageNeutralId);

        // Check the job is found. If not, throw 404
        if (empty($jobs)) {
            return $this->notFoundAction();
        }

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()  && !isset($_POST['reject'])) {
            $files = $request->getFiles();
            $post = $request->getPost();
            $jobDict = [];

            foreach ($jobs as $job) {
                $jobDict[$job->getLanguage()] = $job;
            }

            $companyService->saveJobData($languageNeutralId, $jobDict, $post, $files);
            $companyService->deleteVacancyApprovals($vacancyApprovals);
            return $this->redirect()->toRoute(
                'admin_company/approvalPage'
            );

        } elseif (isset($_POST['reject'])){

            return $this->redirect()->toRoute(
                'admin_company/approvalPage'
            );
        }

        // Initialize the form
        $jobDict = [];
        foreach ($vacancyApprovals as $job) {
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

    public function approvalBannerAction(){

    }

    public function approvalProfileAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $approvalService = $this->getApprovalService();
        $companyForm = $companyService->getCompanyForm();



        // Get parameter
        $companyName = $this->params('slugCompanyName');

        // Get the specified company

        $companyList = $approvalService->getEditableCompaniesBySlugName($companyName);
        $oldCompanyList = $companyService->getEditableCompaniesBySlugName($companyName);
        //echo var_dump($companyList);
        //$companyList = $companyService->getEditableCompaniesBySlugName($companyName);

        // If the company is not found, throw 404
        if (empty($companyList)) {
            $company = null;
            return $this->notFoundAction();
        }


        $company = $companyList[0];
        $oldCompany = $oldCompanyList[0];
        $companyl18 = $approvalService->getApprovalCompanyI18($company->getCompany()->getId());
        //echo var_dump($companyl18);



        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost() && !isset($_POST['reject'])) {
            $post = $request->getPost();
            $post['id'] = $oldCompany->getId();
            if ($companyService->saveCompanyByData(////////////////////
                $oldCompany,
                $post,
                $request->getFiles()
            )) {
                //$companyName = $request->getPost()['slugName'];
                /*return $this->redirect()->toRoute(
                    '/admin/company/approval-page',
                    [
                        'action' => 'approvalPage'
                    ],
                    [],
                    false
                );*/
            }
        }elseif (isset($_POST['reject'])){
            //TODO send email

            //$approvalService->rejectApproval($company->getCompany()->getId());

        }

        // Initialize form
        //echo var_dump($company->getArrayCopy());
        $companyArray = $company->getArrayCopy();
//        $companyArray['languages'] = [];
//        $i = 0;
//        foreach($companyl18 as $language){
//            $companyArray['languages'][$i] = $language->getLanguage();
//            $i++;
//            $companyArray = $companyArray + $language->getArrayCopy();
//        }
        //echo var_dump($companyArray);

        $companyForm->setData($companyArray);
        $companyForm->get('languages')->setValue($companyArray['languages']);

        return new ViewModel([
            'company' => $company,
            'form' => $companyForm
        ]);
    }



    /**
     * Action that allows adding a company
     */
    public function addCompanyAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getCompanyForm();

        // Handle incoming form results
        $request = $this->getRequest();

        // Check if data is valid, and insert when it is
        if ($request->isPost()) {
            $companies = $companyService->insertCompanyByData(
                $request->getPost(),
                $request->getFiles()
            );

            $company = $companies[0];
            $newcompany = $companies[1];

            if (!is_null($company)) {
                //Send activation email
                $this->getCompanyEmailService()->sendActivationEmail($company, $newcompany);

                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/default',
                    [
                        'action' => 'edit',
                        'slugCompanyName' => $company->getSlugName(),
                    ]
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
        return new ViewModel([
            'form' => $companyForm,
        ]);
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
        $companyAccountController = $this->getCompanyAccountController();

        // Get parameter
        $companyName = $this->params('slugCompanyName');
        $type = $this->params('type');

        // Get form
        $packageForm = $companyService->getPackageForm($type);

        //Set the values for the selection element
        if ($type === 'highlight') {
            $packageForm->get('vacancy_id')
                ->setValueOptions($companyAccountController->getVacancyNames(
                    $companyAccountController->getHighlightableVacancies(
                        $this->getCompanyService()->getCompanyBySlugName(
                            $companyName
                        )->getId()
                    )
                ));
        }


        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            $files = $request->getFiles();

            if ($companyService->insertPackageForCompanySlugNameByData(
                $companyName,
                $request->getPost(),
                $files['banner'],
                $type
            )) {
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
        return new ViewModel([
            'form' => $packageForm,
            'type' => $type,
        ]);
    }

    /**
     * Action that allows adding a job
     *
     *
     */
    public function addJobAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobFormCompany();

        // Get parameters
        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageId');

        //get company
        $companyMapper = $this->getCompanyMapper();
        $company = $companyMapper->findCompanyBySlugName($companyName);

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
                    'admin_company/editCompany/editPackage',
                    [
                        'slugCompanyName' => $companyName,
                        'packageId' => $packageId
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
                    'packageId' => $packageId
                ]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'form' => $companyForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);

        $vm->setTemplate('company/admin/edit-job');

        return $vm;
    }

    /**
     * Action that displays a form for editing a category
     *
     *
     */
    public function editCategoryAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $categoryForm = $companyService->getCategoryForm();

        // Get parameter
        $languageNeutralId = $this->params('languageNeutralCategoryId');
        if ($languageNeutralId === null) {
            // The parameter is invalid or non-existent
            return $this->notFoundAction();
        }

        // Get the specified category
        $categories = $companyService->getAllCategoriesById($languageNeutralId);

        // If the category is not found, throw 404
        if (empty($categories)) {
            return $this->notFoundAction();
        }

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $categoryDict = [];

            foreach ($categories as $category) {
                $categoryDict[$category->getLanguage()] = $category;
            }

            $companyService->saveCategoryData($languageNeutralId, $categoryDict, $post);
        }

        // Initialize form
        $categoryDict = [];
        foreach ($categories as $category) {
            $categoryDict[$category->getLanguage()] = $category;
        }

        $languages = array_keys($categoryDict);
        $categoryForm->setLanguages($languages);
        $categoryForm->bind($categoryDict);

        return new ViewModel([
            'form' => $categoryForm,
            'category' => $categories,
            'languages' => $this->getLanguageDescriptions(),
        ]);
    }

    /**
     * Action that displays a form for editing a sector
     *
     *
     */
    public function editSectorAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $sectorForm = $companyService->getSectorForm();

        // Get parameter
        $languageNeutralId = $this->params('languageNeutralSectorId');
        if ($languageNeutralId === null) {
            // The parameter is invalid or non-existent
            return $this->notFoundAction();
        }

        // Get the specified category
        $sectors = $companyService->getAllSectorsById($languageNeutralId);

        // If the category is not found, throw 404
        if (empty($sectors)) {
            return $this->notFoundAction();
        }

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $sectorDict = [];

            foreach ($sectors as $sector) {
                $categoryDict[$sector->getLanguage()] = $sector;
            }

            $companyService->saveSectorData($languageNeutralId, $sectorDict, $post);
        }

        // Initialize form
        $sectorDict = [];
        foreach ($sectors as $sector) {
            $sectorDict[$sector->getLanguage()] = $sector;
        }

        $languages = array_keys($sectorDict);
        $sectorForm->setLanguages($languages);
        $sectorForm->bind($sectorDict);

        return new ViewModel([
            'form' => $sectorForm,
            'category' => $sectors,
            'languages' => $this->getLanguageDescriptions(),
        ]);
    }

    /**
     * Action that displays a form for editing a label
     *
     *
     */
    public function editLabelAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $labelForm = $companyService->getLabelForm();

        // Get parameter
        $languageNeutralId = $this->params('languageNeutralLabelId');
        if ($languageNeutralId === null) {
            // The parameter is invalid or non-existent
            return $this->notFoundAction();
        }

        // Get the specified label
        $labels = $companyService->getAllLabelsById($languageNeutralId);

        // If the label is not found, throw 404
        if (empty($labels)) {
            return $this->notFoundAction();
        }

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            $labelDict = [];

            foreach ($labels as $label) {
                $labelDict[$label->getLanguage()] = $label;
            }

            $companyService->saveLabelData($languageNeutralId, $labelDict, $post);
        }

        // Initialize form
        $labelDict = [];
        foreach ($labels as $label) {
            $labelDict[$label->getLanguage()] = $label;
        }

        $languages = array_keys($labelDict);
        $labelForm->setLanguages($languages);
        $labelForm->bind($labelDict);

        return new ViewModel([
            'form' => $labelForm,
            'label' => $labels,
            'languages' => $this->getLanguageDescriptions(),
        ]);
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
            return $this->notFoundAction();
        }

        $company = $companyList[0];

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost();
            if ($companyService->saveCompanyByData(
                $company,
                $post,
                $request->getFiles()
            )) {
                $companyName = $request->getPost()['slugName'];
                return $this->redirect()->toRoute(
                    'admin_company/default',
                    [
                        'action' => 'edit',
                        'slugCompanyName' => $companyName,
                    ],
                    [],
                    false
                );
            }
        }

        // Initialize form
        $companyForm->setData($company->getArrayCopy());
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
        return new ViewModel([
            'company' => $company,
            'form' => $companyForm,
        ]);
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
        $companyAccountController = $this->getCompanyAccountController();

        // Get the parameters
        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageId');


        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageId);

        $companyId = $package->getCompany()->getId();

        $type = $package->getType();

        // Get form
        $packageForm = $companyService->getPackageForm($type);

        $translator = $companyService->getTranslator();
        $locale = $translator->getLocale();

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->savePackageByData($package, $request->getPost(), $request->getFiles())) {
                    return $this->redirect()->toRoute('admin_company/editHighlight');
                // TODO: possibly redirect to company
            }
        }
        // TODO: display error page when package is not found

        // Initialize form
        $packageForm->bind($package);

        //Set the values for the selection element
        if ($type === 'highlight') {
            $packageForm->get('vacancy_id')->setValueOptions($companyService->getHighlightsForCompany($companyId, $locale));
            $packageForm->get('vacancy_id')->setValue($package->getVacancy()->getId());
        }

        $packageForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCompany/editPackage',
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

    /**
     * Action that displays a form for editing a job
     *
     *
     */
    public function editJobAction()
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
            $companyService->deleteCompaniesBySlug($slugName);
            return $this->redirect()->toRoute('admin_company');
        }

        return $this->notFoundAction();
    }

    public function addCategoryAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $categoryForm = $companyService->getCategoryForm();

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $category = $companyService->createCategory($request->getPost());

            if (is_numeric($category)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editCategory',
                    [
                        'languageNeutralCategoryId' => $category,
                    ]
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $categoryForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                ['action' => 'addCategory']
            )
        );
        // Initialize the view
        return new ViewModel([
            'form' => $categoryForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);
    }

    public function addSectorAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $sectorForm = $companyService->getSectorForm();

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $sector = $companyService->createSector($request->getPost());

            if (is_numeric($sector)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editSector',
                    [
                        'languageNeutralSectorId' => $sector,
                    ]
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $sectorForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                ['action' => 'addSector']
            )
        );
        // Initialize the view
        return new ViewModel([
            'form' => $sectorForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);
    }

    public function addLabelAction()
    {
        // Get useful stuff
        $companyService = $this->getCompanyService();
        $labelForm = $companyService->getLabelForm();

        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $label = $companyService->createLabel($request->getPost());

            if (is_numeric($label)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/editLabel',
                    [
                        'languageNeutralLabelId' => $label,
                    ]
                );
            }
        }

        // The form was not valid, or we did not get data back

        // Initialize the form
        $labelForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/default',
                ['action' => 'addLabel']
            )
        );
        // Initialize the view
        return new ViewModel([
            'form' => $labelForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);
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
        $packageId = $this->params('packageId');
        $companyName = $this->params('slugCompanyName');

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->deletePackage($packageId);
            return $this->redirect()->toRoute(
                'admin_company/editCompany',
                ['slugCompanyName' => $companyName]
            );
        }

        return $this->notFoundAction();
    }

    /**
     * Action to delete a job.
     */
    public function deleteJobAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            return $this->notFoundAction();
        }

        $jobId = $this->params('languageNeutralJobId');

        $this->getCompanyService()->deleteJob($jobId);

        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageId');

        // Redirect to package page
        return $this->redirect()->toRoute(
            'admin_company/editCompany/editPackage',
            [
                'slugCompanyName' => $companyName,
                'packageId' => $packageId
            ]
        );
    }

    /**
     * Action to delete a job.
     */
    public function editHighlightAction()
    {
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();

        return new ViewModel([
            'highlightList' => $companyService->getHighlightsListAll($translator->getLocale()),
            'categoryList' => $companyService->getCategoryList(false),
        ]);
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
     * Method that returns the service object for the approval module.
     *
     * @return ApprovalService
     */
    protected function getApprovalService()
    {
        return $this->getServiceLocator()->get('company_service_approval');
    }


    /**
     * Method that returns the service object for the company module.
     *
     * @return CompanyModel
     */
    protected function getCompanyModel()
    {
        return $this->getServiceLocator()->get('company_model_company');
    }

    /**
     * Method that returns the service object for the company module.
     *
     * @return CompanyMapper
     */
    protected function getCompanyMapper()
    {
        return $this->getServiceLocator()->get('company_mapper_company');
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
     * Get the email service.
     *
     * @return CompanyEmailService
     */
    public function getCompanyEmailService()
    {
        return $this->getServiceLocator()->get('user_service_companyemail');

    }



    /**
     * Method that returns the service object for the company module.
     *
     * @return CompanyAccountController
     */
    protected function getCompanyAccountController()
    {
        return $this->getServiceLocator()->get('decision_controller_companyAccountController');
    }

    /**
     * Get the email service.
     *
     * @return Job
     */
    public function getJobMapper()
    {
        return $this->getServiceLocator()->get('company_mapper_job');

    }
}
