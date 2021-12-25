<?php

namespace Decision\Controller;

use Decision\Service\Member as MemberService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class MemberApiController extends AbstractActionController
{
    /**
     * @var MemberService
     */
    private MemberService $memberService;

    /**
     * MemberApiController constructor.
     *
     * @param MemberService $memberService
     */
    public function __construct(MemberService $memberService)
    {
        $this->memberService = $memberService;
    }

    public function lidnrAction(): JsonModel
    {
        $lidnr = $this->params()->fromRoute('lidnr');

        $member = $this->memberService->findMemberByLidNr($lidnr);

        if ($member) {
            return new JsonModel($member->toApiArray());
        }

        return new JsonModel([]);
    }
}
