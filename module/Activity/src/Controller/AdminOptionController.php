<?php

namespace Activity\Controller;

use Activity\Mapper\ActivityOptionCreationPeriod as ActivityOptionCreationPeriodMapper;
use Activity\Service\{
    AclService,
    ActivityCalendar as ActivityCalendarService,
};
use DateTime;
use Decision\Service\Organ as OrganService;
use Laminas\Http\{
    Request,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class AdminOptionController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityCalendarService $activityCalendarService,
        private readonly OrganService $organService,
        private readonly ActivityOptionCreationPeriodMapper $activityOptionCreationPeriodMapper,
    ) {
    }

    public function indexAction(): ViewModel
    {
        if (!$this->aclService->isAllowed('view', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate(
                'You are not allowed to administer option calendar periods',
            ));
        }

        return new ViewModel([
            'current' => $this->activityOptionCreationPeriodMapper->getCurrentActivityOptionCreationPeriods(),
            'upcoming' => $this->activityOptionCreationPeriodMapper->getUpcomingActivityOptionCreationPeriods(),
        ]);
    }

    public function addAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate(
                'You are not allowed to create option calendar periods',
            ));
        }

        $form = $this->activityCalendarService->getCalendarPeriodForm();
        $organs = $this->organService->getEditableOrgans();
        $organCount = count($organs);
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->get('maxActivities')->setCount($organCount);
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->activityCalendarService->createOptionPlanningPeriod($form->getData())) {
                    return $this->redirectWithMessage(
                        true,
                        $this->translator->translate(
                            'Option planning period created successfully.',
                        ),
                    );
                }
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

        return new ViewModel([
            'form' => $form,
            'action' => $this->translator->translate('Add Option Period'),
        ]);
    }

    public function deleteAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('delete', 'activity_calendar_period')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to delete option calendar periods'),
            );
        }

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $optionCreationPeriodId = (int) $this->params()->fromRoute('id');
            $optionCreationPeriod = $this->activityCalendarService->getOptionCreationPeriod($optionCreationPeriodId);

            if (null !== $optionCreationPeriod) {
                if ($optionCreationPeriod->getEndPlanningTime() < new DateTime('now')) {
                    return $this->redirectWithMessage(
                        false,
                        $this->translator->translate('Past option planning periods cannot be deleted.'),
                    );
                }

                $this->activityCalendarService->deleteOptionCreationPeriod($optionCreationPeriod);

                return $this->redirectWithMessage(
                    true,
                    $this->translator->translate('Option planning period deleted.'),
                );
            }
        }

        return $this->notFoundAction();
    }

    public function editAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('edit', 'activity_calendar_period')) {
            throw new NotAllowedException($this->translator->translate(
                'You are not allowed to edit option calendar periods',
            ));
        }

        $optionCreationPeriodId = (int) $this->params()->fromRoute('id');
        $optionCreationPeriod = $this->activityCalendarService->getOptionCreationPeriod($optionCreationPeriodId);

        if (null === $optionCreationPeriod) {
            return $this->notFoundAction();
        }

        if ($optionCreationPeriod->getBeginPlanningTime() < new DateTime('now')) {
            return $this->redirectWithMessage(
                false,
                $this->translator->translate(
                    'This option planning period cannot be edited.',
                ),
            );
        }

        $form = $this->activityCalendarService->getCalendarPeriodForm();
        $organCount = $optionCreationPeriod->getMaxActivities()->count();
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->get('maxActivities')->setCount($organCount);
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->activityCalendarService->updateOptionPlanningPeriod($optionCreationPeriod, $form->getData())) {
                    return $this->redirectWithMessage(
                        true,
                        $this->translator->translate(
                            'Option planning period has been updated.',
                        ),
                    );
                }
            }
        }

        $optionCreationPeriodData = $optionCreationPeriod->toArray();
        unset($optionCreationPeriodData['id']);

        // Fix organ names.
        foreach ($optionCreationPeriodData['maxActivities'] as &$maxActivity) {
            $maxActivity['id'] = $maxActivity['organ']->getId();
            $maxActivity['name'] = $maxActivity['organ']->getName();
            unset($maxActivity['organ']);
        }

        $form->get('maxActivities')->setCount($organCount);
        $form->setData($optionCreationPeriodData);

        $viewModel = new ViewModel([
            'form' => $form,
            'action' => $this->translator->translate('Edit Option Period'),
        ]);
        $viewModel->setTemplate('activity/admin-option/add.phtml');

        return $viewModel;
    }

    /**
     * @param bool $success
     * @param string $message
     *
     * @return Response
     */
    private function redirectWithMessage(
        bool $success,
        string $message,
    ): Response {
        if ($success) {
            $this->plugin('FlashMessenger')->addSuccessMessage($message);
        } else {
            $this->plugin('FlashMessenger')->addErrorMessage($message);
        }

        return $this->redirect()->toRoute('activity_admin_options');
    }
}
