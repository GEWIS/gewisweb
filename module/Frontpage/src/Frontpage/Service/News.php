<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use DateTime;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Frontpage\Mapper\NewsItem;
use Frontpage\Model\NewsItem as NewsItemModel;
use Laminas\Mvc\I18n\Translator;
use Laminas\Permissions\Acl\Acl;
use User\Model\User;
use User\Permissions\NotAllowedException;

/**
 * News service.
 */
class News extends AbstractAclService
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var User|string
     */
    private $userRole;

    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var NewsItem
     */
    private $newsItemMapper;

    /**
     * @var \Frontpage\Form\NewsItem
     */
    private $newsItemForm;

    public function __construct(Translator $translator, $userRole, Acl $acl, NewsItem $newsItemMapper, \Frontpage\Form\NewsItem $newsItemForm)
    {
        $this->translator = $translator;
        $this->userRole = $userRole;
        $this->acl = $acl;
        $this->newsItemMapper = $newsItemMapper;
        $this->newsItemForm = $newsItemForm;
    }

    public function getRole()
    {
        return $this->userRole;
    }

    /**
     * Get the translator.
     *
     * @return Translator
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * Returns a single NewsItem by its id.
     *
     * @param int $newsItem
     *
     * @return NewsItemModel|null
     */
    public function getNewsItemById($newsItem)
    {
        return $this->newsItemMapper->findNewsItemById($newsItem);
    }

    /**
     * Returns a paginator adapter for paging through news items.
     *
     * @return DoctrinePaginator
     */
    public function getPaginatorAdapter()
    {
        if (!$this->isAllowed('list')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to list all news items.'));
        }

        return $this->newsItemMapper->getPaginatorAdapter();
    }

    /**
     * Retrieves a certain number of news items sorted descending by their date.
     *
     * @param int $count
     *
     * @return array
     */
    public function getLatestNewsItems($count)
    {
        return $this->newsItemMapper->getLatestNewsItems($count);
    }

    /**
     * Creates a news item.
     *
     * @param array $data form post data
     *
     * @return bool|NewsItemModel false if creation was not successful
     */
    public function createNewsItem($data)
    {
        $form = $this->getNewsItemForm();
        $newsItem = new NewsItemModel();
        $form->bind($newsItem);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $newsItem->setDate(new DateTime());
        $this->newsItemMapper->persist($newsItem);
        $this->newsItemMapper->flush();

        return $newsItem;
    }

    /**
     * @param int   $newsItemId
     * @param array $data       form post data
     *
     * @return bool
     */
    public function updateNewsItem($newsItemId, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit news items.'));
        }
        $form = $this->getNewsItemForm($newsItemId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->newsItemMapper->flush();

        return true;
    }

    /**
     * Removes a news item.
     *
     * @param int $newsItemId the id of the news item to remove
     */
    public function deleteNewsItem($newsItemId)
    {
        $newsItem = $this->getNewsItemById($newsItemId);
        $this->newsItemMapper->remove($newsItem);
        $this->newsItemMapper->flush();
    }

    /**
     * Get the NewsItem form.
     *
     * @param int $newsItemId
     *
     * @return \Frontpage\Form\NewsItem
     */
    public function getNewsItemForm($newsItemId = null)
    {
        if (!$this->isAllowed('create')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create news items.'));
        }
        $form = $this->newsItemForm;

        if (!is_null($newsItemId)) {
            $newsItem = $this->getNewsItemById($newsItemId);
            $form->bind($newsItem);
        }

        return $form;
    }

    /**
     * Get the Acl.
     *
     * @return Acl
     */
    public function getAcl()
    {
        return $this->acl;
    }

    /**
     * Get the default resource ID.
     *
     * @return string
     */
    protected function getDefaultResourceId()
    {
        return 'news_item';
    }
}
