<?php

namespace Bobbyshaw\RedditPersonalities;


use DarrynTen\PersonalityInsightsPhp\PersonalityInsights;
use Dotenv\Dotenv;
use GuzzleHttp\Exception\ClientException;

class TraitsService
{

    /**
     * @var
     */
    protected $service;

    public function __construct()
    {
        $path = __DIR__ . "/../";

        $dotenv = new Dotenv(__DIR__ . "/../");
        $dotenv->load();
        $dotenv->required(['WATSON_USERNAME', 'WATSON_PASSWORD'])->notEmpty();


        $config = [
            'username' => getenv('WATSON_USERNAME'),
            'password' => getenv('WATSON_PASSWORD')
        ];

        $this->service = new PersonalityInsights($config);

    }

    /**
     * @param string $text
     * @return array - traits and probabilities
     */
    public function getTraits($text)
    {
        $traits = [];

        $this->service->addText($text);

        $insights = [];

        try {
            $insights = $this->service->getInsights();
        } catch (ClientException $e) {

        }

        return $insights;
    }
}
