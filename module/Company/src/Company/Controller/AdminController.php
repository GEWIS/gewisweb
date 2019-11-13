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
        $vm = new ViewModel([
            'form' => $packageForm,
            'type' => $type,
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
                        'packageID' => $packageId
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
        $categoryId = $this->params('categoryID');

        // Get the specified company
        $categories = $companyService->getAllCategoriesById($categoryId);

        // If the company is not found, throw 404
        if (empty($categories)) {
            $company = null;
            return $this->notFoundAction();
        }

        // Initialize form
        $categoriesDict = [];
        foreach ($categories as $category) {
            $categoriesDict[$category->getLanguage()] = $category;
        }
        $categoryForm->bind($categoriesDict);
        $categoryForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editCategory',
                [
                    'categoryID' => $categoryId,
                ]
            )
        );
        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->saveCategory(
                $request->getPost()
            )){
                return $this->redirect()->toRoute(
                    'admin_company/editCategory',
                    [
                        'categoryID' => $categoryId,
                    ]
                );

            }
        }

        $vm = new ViewModel([
            'categories' => $categories,
            'form' => $categoryForm,
            'languages' => $this->getLanguageDescriptions(),
        ]);

        return $vm;
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
        $labelId = $this->params('labelID');

        // Get the specified company
        $labels = $companyService->getAllLabelsById($labelId);

        // If the company is not found, throw 404
        if (empty($labels)) {
            $company = null;
            return $this->notFoundAction();
        }

        // Initialize form
        $labelsDict = [];
        foreach ($labels as $label) {
            $labelsDict[$label->getLanguage()] = $label;
        }
        $labelForm->bind($labelsDict);
        $labelForm->setAttribute(
            'action',
            $this->url()->fromRoute(
                'admin_company/editLabel',
                [
                    'labelID' => $labelId,
                ]
            )
        );
        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            if ($companyService->saveLabel(
                $request->getPost()
            )){
                return $this->redirect()->toRoute(
                    'admin_company/editLabel',
                    [
                        'labelID' => $labelId,
                    ]
                );

            }
        }

        $vm = new ViewModel([
            'labels' => $labels,
            'form' => $labelForm,
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
        $vm = new ViewModel([
            'company' => $company,
            'form' => $companyForm,
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

        // Get the parameters
        $companyName = $this->params('slugCompanyName');
        $packageID = $this->params('packageID');

        // Get the specified package (Assuming it is found)
        $package = $companyService->getEditablePackage($packageID);
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
                    'packageID' => $packageID,
                    'slugCompanyName' => $companyName,
                    'type' => $type,
                ]
            )
        );

        // Initialize the view
        $vm = new ViewModel([
            'package' => $package,
            'companyName' => $companyName,
            'form' => $packageForm,
            'type' => $type,
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
            $categories = $companyService->insertCategoryByData(
                $request->getPost(),
                $request->getFiles()
            );
            if (!is_null($categories)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/default',
                    [
                        'action' => 'editCategory',
                        'slugCompanyName' => $categories['nl']->getLanguageNeutralId(),
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
            $labels = $companyService->insertLabelByData(
                $request->getPost(),
                $request->getFiles()
            );
            if (!is_null($labels)) {
                // Redirect to edit page
                return $this->redirect()->toRoute(
                    'admin_company/default',
                    [
                        'action' => 'editLabel',
                        'slugCompanyName' => $labels['nl']->getLanguageNeutralId(),
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
        $packageID = $this->params('packageID');
        $companyName = $this->params('slugCompanyName');

        // Handle incoming form data
        $request = $this->getRequest();
        if ($request->isPost()) {
            $companyService->deletePackage($packageID);
            return $this->redirect()->toRoute(
                'admin_company/editCompany',
                ['slugCompanyName' => $companyName]
            );
        }

        return $this->notFoundAction();
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
