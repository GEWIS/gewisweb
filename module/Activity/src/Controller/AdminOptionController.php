<?php

namespace Activity\Controller;

use Activity\Service\{
    AclService,
    ActivityCalendar as ActivityCalendarService,
};
use Decision\Service\Organ as OrganService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class AdminOptionController extends AbstractActionController
{
    /**
     * @var ActivityCalendarService
     */
    private ActivityCalendarService $activityCalendarService;

    /**
     * @var OrganService
     */
    private OrganService $organService;

    /**
     * @var AclService
     */
    private AclService $aclService;

    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * AdminOptionController constructor.
     *
     * @param ActivityCalendarService $activityCalendarService
     * @param OrganService $organService
     * @param AclService $aclService
     * @param Translator $translator
     */
    public function __construct(
        ActivityCalendarService $activityCalendarService,
        OrganService $organService,
        AclService $aclService,
        Translator $translator,
    ) {
        $this->activityCalendarService = $activityCalendarService;
        $this->organService = $organService;
        $this->aclService = $aclService;
        $this->translator = $translator;
    }

    public function indexAction()
    {
        if (!$this->aclService->isAllowed('view', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to administer option calendar periods'));
        }

        return new ViewModel();
    }

    public function addAction()
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to create option calendar periods'));
        }

        $form = $this->activityCalendarService->getCalendarPeriodForm();
        $organs = $this->organService->getEditableOrgans();
        $organCount = count($organs);
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->get('maxActivities')->setCount($organCount);
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                // do something
            }
        }

        if (0 === $form->get('maxActivities')->count()) {
            $form->get('maxActivities')->setCount($organCount);

            $organArray = [];
            foreach ($organs as $organ) {
                $organArray[] = [
                    'id' => $organ->getId(),
                    'name' => $organ->getName(),
                    'value' => 0,
                ];
            }

            $form->get('maxActivities')->populateValues($organArray);
        }

        return new ViewModel(['form' => $form]);
    }

    public function deleteAction()
    {
        if (!$this->aclService->isAllowed('delete', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to delete option calendar periods'));
        }

        return new ViewModel();
    }

    public function editAction()
    {
        if (!$this->aclService->isAllowed('edit', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate('You are not allowed to edit option calendar periods'));
        }

        return new ViewModel();
    }
}
