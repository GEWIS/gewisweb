<?php

declare(strict_types=1);

namespace Application\Service;

use Laminas\Cache\Exception\ExceptionInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\{
    Client,
    Request,
};
use Laminas\Json\Json;
use Laminas\Mvc\I18n\Translator;

class Infimum
{
    public function __construct(
        private readonly AbstractAdapter $infimumCache,
        private readonly Translator $translator,
        private readonly array $infimumConfig,
    ) {
    }

    /**
     * @return string
     * @throws ExceptionInterface
     */
    public function getInfimum(): string
    {
        // Check if we have a cached infimum, return it if true.
        if ($this->infimumCache->hasItem('infimum')) {
            return $this->infimumCache->getItem('infimum');
        }

        // Request a new infimum from the Supremum API.
        $client = new Client();
        $request = new Request();

        $request->setMethod(Request::METHOD_GET)
            ->setUri($this->infimumConfig['supremum_api_url'])
            ->getHeaders()->addHeaders([
                    $this->infimumConfig['supremum_api_header'] => $this->infimumConfig['supremum_api_key'],
            ]);
        $client->setAdapter(Curl::class)
            ->setEncType('application/json');

        $response = $client->send($request);

        // Check if the request was successful.
        if (200 === $response->getStatusCode()) {
            $responseContent = Json::decode($response->getBody(), Json::TYPE_ARRAY);

            // Check if an Infimum is returned.
            if (array_key_exists('content', $responseContent)) {
                // Cache the infimum to reduce the number of requests that need to be executed.
                $this->infimumCache->setItem('infimum', $responseContent['content']);

                return $responseContent['content'];
            }
        }

        return $this->translator->translate('Unable to retrieve infimum.');
    }
}
