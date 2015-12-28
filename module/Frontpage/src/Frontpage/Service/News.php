<?php

namespace Frontpage\Service;

use Application\Service\AbstractAclService;
use Frontpage\Model\NewsItem as NewsItemModel;

/**
 * News service
 */
class News extends AbstractAclService
{

    /**
     * Returns a single NewsItem by its id
     *
     * @param integer $newsItem
     * @return \Frontpage\Model\NewsItem|null
     */
    public function getNewsItemById($newsItem)
    {
        return $this->getNewsItemMapper()->findNewsItemById($newsItem);
    }

    /**
     * Returns a paginator adapter for paging through news items.
     *
     * @return \DoctrineORMModule\Paginator\Adapter\DoctrinePaginator
     */
    public function getPaginatorAdapter()
    {
        return $this->getNewsItemMapper()->getPaginatorAdapter();
    }

    /**
     * Creates a news item.
     *
     * @param array $data form post data
     * @return bool|NewsItemModel false if creation was not successful.
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

        $newsItem->setDate(new \DateTime());
        $this->getNewsItemMapper()->persist($newsItem);
        $this->getNewsItemMapper()->flush();

        return $newsItem;
    }

    /**
     * @param integer $newsItemId
     * @param array $data form post data
     * @return bool
     */
    public function updateNewsItem($newsItemId, $data)
    {
        if (!$this->isAllowed('edit')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to edit news items.')
            );
        }
        $form = $this->getNewsItemForm($newsItemId);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->getNewsItemMapper()->flush();

        return true;
    }

    /**
     * Removes a news item.
     *
     * @param integer $newsItemId The id of the news item to remove.
     */
    public function deleteNewsItem($newsItemId)
    {
        $newsItem = $this->getNewsItemById($newsItemId);
        $this->getNewsItemMapper()->remove($newsItem);
        $this->getNewsItemMapper()->flush();
    }

    /**
     * Get the NewsItem form.
     *
     * @param integer $newsItemId
     *
     * @return \Frontpage\Form\NewsItem
     */
    public function getNewsItemForm($newsItemId = null)
    {
        if (!$this->isAllowed('create')) {
            throw new \User\Permissions\NotAllowedException(
                $this->getTranslator()->translate('You are not allowed to create news items.')
            );
        }
        $form = $this->sm->get('frontpage_form_news_item');

        if(!is_null($newsItemId)) {
            $newsItem = $this->getNewsItemById($newsItemId);
            $form->bind($newsItem);
        }

        return $form;
    }

    /**
     * Get the news item mapper.
     *
     * @return \Frontpage\Mapper\NewsItem
     */
    public function getNewsItemMapper()
    {
        return $this->sm->get('frontpage_mapper_news_item');
    }

    /**
     * Get the Acl.
     *
     * @return \Zend\Permissions\Acl\Acl
     */
    public function getAcl()
    {
        return $this->getServiceManager()->get('frontpage_acl');
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
