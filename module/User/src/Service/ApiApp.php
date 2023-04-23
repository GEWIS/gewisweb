<?php

declare(strict_types=1);

namespace User\Service;

use DateTime;
use Firebase\JWT\JWT;
use User\Mapper\ApiAppAuthentication as ApiAppAuthenticationMapper;
use User\Model\{
    ApiApp as ApiAppModel,
    ApiAppAuthentication as ApiAppAuthenticationModel,
    User as UserModel,
};

class ApiApp
{
    public function __construct(private readonly ApiAppAuthenticationMapper $apiAppAuthenticationMapper)
    {
    }

    /**
     * Get a callback from an appId and a user identity.
     *
     * @param ApiAppModel $app
     * @param UserModel $user
     *
     * @return string
     */
    public function callbackWithToken(
        ApiAppModel $app,
        UserModel $user,
    ): string {
        $member = $user->getMember();

        $token = [
            'iss' => 'https://gewis.nl/',
            'exp' => (new DateTime('+5 min'))->getTimestamp(),
            'iat' => (new DateTime())->getTimestamp(),
            'nonce' => bin2hex(openssl_random_pseudo_bytes(16)),
        ];

        foreach ($app->getClaims() as $claim) {
            $token[$claim->value] = $claim->getValue($member);
        }

        // Log the authentication.
        $this->logAuthentication($app, $user);

        return $app->getCallback() . '?token=' . JWT::encode($token, $app->getSecret(), 'HS512');
    }

    protected function logAuthentication(
        ApiAppModel $app,
        UserModel $user,
    ): void {
        $authentication = new ApiAppAuthenticationModel();
        $authentication->setUser($user);
        $authentication->setApiApp($app);
        $authentication->setTime(new DateTime('now'));

        $this->apiAppAuthenticationMapper->persist($authentication);
        $this->apiAppAuthenticationMapper->flush();
    }
}
