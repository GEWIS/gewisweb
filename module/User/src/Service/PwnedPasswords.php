<?php

namespace User\Service;

use Laminas\Http\{
    Client,
    Request,
};
use Laminas\Http\Client\Adapter\Curl;
use SensitiveParameter;

/**
 * A service providing a check against a Pwned Passwords API. This is a separate service from the {@link User} service
 * to prevent cyclic dependencies.
 */
readonly class PwnedPasswords
{
    public function __construct(private string $pwnedPasswordsHost)
    {
    }

    /**
     * Check a password against a Pwned Passwords instance to determine if it was leaked in a public breach. To not send
     * the plaintext password over the internet, we SHA1 it. This is different from the official Pwned Passwords API,
     * were only the first few characters of the hash are transmitted.
     *
     * The GEWIS version of Pwned Passwords uses a gigantic bloom filter to store the passwords as that is more
     * efficient, however, the trade-off is having to use the full hash.
     *
     * This function returns `true` iff the password is known to be leaked. All other cases, including failures to make
     * the request will return `false`.
     */
    public function isPasswordLeaked(#[SensitiveParameter] string $password): bool
    {
        $client = new Client();
        $request = new Request();

        $request->setMethod(Request::METHOD_GET)
            ->setUri($this->pwnedPasswordsHost . '/' . strtoupper(sha1($password)));
        $client->setAdapter(Curl::class)
            ->setEncType('text/plain');

        // If the request fails, we assume it is not breached.
        try {
            $response = $client->send($request);
        } catch (Exception) {
            return false;
        }

        // Check if the request was successful.
        if (200 === $response->getStatusCode()) {
            $responseContent = (int) $response->getContent();

            // Determine if the password was pwned.
            if (1 === $responseContent) {
                return true;
            }
        }

        return false;
    }
}
