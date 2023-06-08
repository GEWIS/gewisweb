<?php

declare(strict_types=1);

namespace Activity\Controller;

use Activity\Form\ActivityCalendarProposal as ActivityCalendarProposalForm;
use Activity\Service\AclService;
use Activity\Service\ActivityCalendar as ActivityCalendarService;
use Activity\Service\ActivityCalendarForm as ActivityCalendarFormService;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\ViewModel;
use User\Permissions\NotAllowedException;

class ActivityCalendarController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        private readonly AclService $aclService,
        private readonly Translator $translator,
        private readonly ActivityCalendarService $calendarService,
        private readonly ActivityCalendarFormService $calendarFormService,
        private readonly ActivityCalendarProposalForm $calendarProposalForm,
        private readonly array $calendarConfig,
    ) {
    }

    public function indexAction(): ViewModel
    {
        $config = $this->calendarConfig;

        return new ViewModel(
            [
                'options' => $this->calendarService->getUpcomingOptions(),
                'editableOptions' => $this->calendarService->getEditableUpcomingOptions(),
                'APIKey' => $config['google_api_key'],
                'calendarKey' => $config['google_calendar_key'],
                'success' => (bool) $this->params()->fromQuery('success', false),
                'canCreate' => $this->calendarService->canCreateProposal(),
                'canApprove' => $this->calendarService->canApproveOption(),
            ],
        );
    }

    public function deleteAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->deleteOption((int) $request->getPost()['option_id']);

            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function approveAction(): Response|ViewModel
    {
        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $this->calendarService->approveOption((int) $request->getPost()['option_id']);

            return $this->redirect()->toRoute('activity_calendar');
        }

        return $this->notFoundAction();
    }

    public function createAction(): Response|ViewModel
    {
        if (!$this->aclService->isAllowed('create', 'activity_calendar_proposal')) {
            throw new NotAllowedException(
                $this->translator->translate('You are not allowed to create activity proposals'),
            );
        }

        $form = $this->calendarProposalForm;

        /** @var Request $request */
        $request = $this->getRequest();

        if ($request->isPost()) {
            $form->setData($request->getPost()->toArray());

            if ($form->isValid()) {
                if ($this->calendarService->createProposal($form->getData())) {
                    return $this->redirect()->toRoute(
                        'activity_calendar',
                        [],
                        [
                            'query' => [
                                'success' => 'true',
                            ],
                        ],
                    );
                }
            }
        }

        $periods = $this->calendarFormService->getCurrentPeriods();
        $createAlways = $this->aclService->isAllowed('create_always', 'activity_calendar_proposal');

        return new ViewModel(
            [
                'periods' => $periods,
                'createAlways' => $createAlways,
                'form' => $form,
            ],
        );
    }
}
