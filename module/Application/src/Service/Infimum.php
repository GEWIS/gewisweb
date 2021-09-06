<?php

namespace Application\Service;

use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Curl;
use Laminas\Http\Request;
use Laminas\Json\Json;
use Laminas\Mvc\I18n\Translator;

class Infimum
{
    /**
     * @var Translator
     */
    private Translator $translator;

    /**
     * @var array
     */
    private array $infimaConfig;

    public function __construct(
        Translator $translator,
        array $infimaConfig,
    ) {
        $this->translator = $translator;
        $this->infimaConfig = $infimaConfig;
    }

    /**
     * @return string
     */
    public function getInfimum(): string
    {
        $client = new Client();
        $request = new Request();

        $request->setMethod(Request::METHOD_GET)
            ->setUri($this->infimaConfig['supremum_api_url'])
            ->getHeaders()->addHeaders([
                    $this->infimaConfig['supremum_api_header'] => $this->infimaConfig['supremum_api_key'],
            ]);
        $client->setAdapter(Curl::class)
            ->setEncType('application/json');

        $response = $client->send($request);

        if (200 === $response->getStatusCode()) {
            $responseContent = Json::decode($response->getBody(), Json::TYPE_ARRAY);

            if (array_key_exists('content', $responseContent)) {
                return $responseContent['content'];
            }
        }

        return $this->translator->translate('Unable to retrieve infimum.');
    }
}
