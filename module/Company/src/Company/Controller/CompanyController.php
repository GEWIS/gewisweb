<?php

namespace Company\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Feed\Writer\Feed;
use Zend\View\Model\FeedModel;

class CompanyController extends AbstractActionController
{
    /**
     *
     * Action to display a list of all nonhidden companies
     *
     */
    public function listAction()
    {
        $companyService = $this->getCompanyService();
        return new ViewModel([
            'companyList' => $companyService->getCompanyList(),
            'translator' => $companyService->getTranslator(),
        ]);

    }

    /**
     *
     * Action to make a feed of all active jobs
     *
     */
    public function jobFeedAction()
    {
        // Get useful stuf
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();
        $jobList = $companyService->getActiveJobList();
        $locale = $translator->getLocale();

        // Sort jobs on timestamp
        usort($jobList, function ($a, $b) {
            $ats = $a->getTimestamp();
            $bts = $b->getTimestamp();
            if ($ats == $bts) {
                return 0;
            }

            return $ats < $bts ? -1 : 1;

        });
        $feed = new Feed();
        $feed->setTitle($translator->translate("Job list"));

        #    $this->url()->fromRoute(
        #        'admin_company/default',
        #        [
        #            'action' => 'editCompany',
        #            'slugCompanyName' => $companyName,
        #        ]
        #    )
        $feed->setFeedLink($this->url()->fromRoute('company/jobList/feed',[],['force_canonical' => true]), 'atom');
        #$feed->addAuthor(array(
        #    'name'  => 'admin',
        #    'email' => 'contact@ourdomain.com',
        #    'uri'   => 'http://www.ourdomain.com',
        #     ));
        $feed->setDescription($translator->translate('Learn about job oppertunities by following this feed.'));
        $feed->setLink($this->url()->fromRoute('company/jobList',[],['force_canonical' => true]));
        $feed->setDateModified(time());
 
        foreach ($jobList as $job) {
            $company = $job->getCompany();
            if ($job->getLanguage() != $locale || $company->getTranslationFromLocale($locale) == null) {
                continue;
            }
            //create entry...
            $entry = $feed->createEntry();
            $entry->setTitle($job->getName());
            $entry->setLink($this->url()->fromRoute(
                'company/companyItem/joblist/job_item',
                [
                    'slugCompanyName' => $company->getSlugName(),
                    'slugJobName' => $job->getSlugName(),
                ],
                ['force_canonical' => true]
            ));


            $description = $job->getDescription();
            if ($description == '') {
                $description = ' ';
            }

            // Render markdown for description and remove all other tags
            $markdownService = $this->getServiceLocator()->get('MaglMarkdown\MarkdownService');
            $description = $markdownService->render(nl2br(strip_tags($description)));
            
            $entry->setDescription($description);
 
            $entry->setDateModified($job->getTimestamp());
            $entry->setDateCreated($job->getTimestamp());
 
            $feed->addEntry($entry);
        }
 
        $feed->export('atom');
        $feedmodel = new FeedModel();
        $feedmodel->setFeed($feed);
 
        return $feedmodel;
    }
    public function showAction()
    {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('slugCompanyName');
        $company = $companyService->getCompanyBySlugName($companyName);
        if (!is_null($company)) {
            return new ViewModel([
                'company' => $company,
                'translator' => $companyService->getTranslator(),
            ]);
        }

        return $this->notFoundAction();
    }

    /**
     *
     * Action that shows the 'company in the spotlight' and the article written by the company in the current language
     *
     */
    public function spotlightAction()
    {
        $companyService = $this->getCompanyService();
        $translator = $companyService->getTranslator();

        $featuredPackage = $companyService->getFeaturedPackage();
        if (!is_null($featuredPackage)) {
            // jobs for a single company
            return new ViewModel([
                'company' => $featuredPackage->getCompany(),
                'featuredPackage' => $featuredPackage,
                'translator' => $translator,
            ]);
        }

        // There is no company is the spotlight, so throw a 404
        $this->getResponse()->setStatusCode(404);
    }

    /**
     *
     * Action that displays a list of all jobs (facaturebank)
     *
     */
    public function jobListAction()
    {
        $companyService = $this->getCompanyService();
        $companyName = $this->params('slugCompanyName');
        if (isset($companyName)) {
            // jobs for a single company
            return new ViewModel([
                'company' => $companyService->getCompanyBySlugName($companyName),
                'jobList' => $companyService->getJobsByCompanyName($companyName),
                'translator' => $companyService->getTranslator(),
                'randomize' => false,
            ]);
        }
        // all jobs
        return new ViewModel([
            'jobList' => $companyService->getJobList(),
            'translator' => $companyService->getTranslator(),
            'randomize' => true,
        ]);
    }

    /**
     *
     * Action to list jobs of a certain company
     *
     */
    public function jobsAction()
    {
        $companyService = $this->getCompanyService();
        $jobName = $this->params('slugJobName');
        $companyName = $this->params('slugCompanyName');
        if ($jobName != null) {
            $jobs = $companyService->getJobsBySlugName($companyName, $jobName);
            if (count($jobs) != 0) {
                return new ViewModel([
                    'job' => $jobs[0],
                    'translator' => $companyService->getTranslator(),
                ]);
            }
            return $this->notFoundAction();
        }
        return new ViewModel([
            'activeJobList' => $companyService->getActiveJobList(),
            'translator' => $companyService->getTranslator(),
        ]);
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
