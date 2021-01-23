<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Company\Service\Company as CompanyService;
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
            'packageFuture' => $companyService->getPackageChangeEvents((new \DateTime())->add(
                new \DateInterval("P1M")
            )),
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
        if ($request->isPost()) {
            // Check if data is valid, and insert when it is
            $company = $companyService->insertCompanyByData(
                $request->getPost(),
                $request->getFiles()
            );
            if (!is_null($company)) {
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

        // Get parameter
        $companyName = $this->params('slugCompanyName');
        $type = $this->params('type');

        // Get form
        $packageForm = $companyService->getPackageForm($type);

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
        // Get useful stuf
        $companyService = $this->getCompanyService();
        $companyForm = $companyService->getJobForm();

        // Get parameters
        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageId');

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
            )){
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

        // Get the parameters
        $companyName = $this->params('slugCompanyName');
        $packageId = $this->params('packageId');

        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageId);
        $type = $package->getType();

        // Get form
        $packageForm = $companyService->getPackageForm($type);


        // Handle incoming form results
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->savePackageByData($package,$request->getPost(), $request->getFiles())) {
                // TODO: possibly redirect to company
            }
        }
        // TODO: display error page when package is not found

        // Initialize form
        $packageForm->bind($package);
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
        $jobForm = $companyService->getJobForm();

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

        // TODO: This is a hotfix for some ORM issues:
        $labels = $companyService->getLabelAssignmentMapper()->findAssignmentsByJobId($jobs[0]->getId());
        // TODO: Instead of doing:
//        $labels = $jobs[0]->getLabels();

        $mapper = $companyService->getLabelMapper();
        $actualLabels = [];
        foreach ($labels as $label) {
            $actualLabel = $label->getLabel();
            $actualLabels[] = $mapper->siblingLabel($actualLabel, 'en');
            $actualLabels[] = $mapper->siblingLabel($actualLabel, 'nl');
        }
        $jobForm->setLabels($actualLabels);
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
     * Method that returns the service object for the company module.
     *
     * @return CompanyService
     */
    protected function getCompanyService()
    {
        return $this->getServiceLocator()->get('company_service_company');
    }
}
