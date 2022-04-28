<?php

namespace User\Service;

use DateTime;
use Firebase\JWT\JWT;
use Decision\Model\Member as MemberModel;
use User\Model\ApiApp as ApiAppModel;

class ApiApp
{
    /**
     * Get a callback from an appId and a user identity.
     *
     * @param ApiAppModel $app
     * @param MemberModel $member
     *
     * @return string
     */
    public function callbackWithToken(
        ApiAppModel $app,
        MemberModel $member,
    ): string {

        $token = [
            'iss' => 'https://gewis.nl/',
            'exp' => (new DateTime('+5 min'))->getTimestamp(),
            'iat' => (new DateTime())->getTimestamp(),
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16)),
        ];

        $claimsToAdd = $app->getClaims();

        // Always provide the lidnr (even if no claims have been declared).
        if (empty($claimsToAdd)) {
            $token['lidnr'] = $member->getLidnr();
        } else {
            foreach ($app->getClaims() as $claim) {
                $token[$claim->value] = $claim->getValue($member);
            }
        }

        return $app->getCallback() . '?token=' . JWT::encode($token, $app->getSecret(), 'HS256');
    }
}
