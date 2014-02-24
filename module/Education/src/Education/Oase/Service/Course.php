<?php

namespace Education\Oase\Service;

use Education\Oase\Client;
use Education\Model\Study as StudyModel;

class Course
{

    /**
     * Client.
     *
     * @var Client
     */
    protected $client;


    /**
     * Constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
}
