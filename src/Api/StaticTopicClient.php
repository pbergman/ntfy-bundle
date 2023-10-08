<?php
declare(strict_types=1);

namespace PBergman\Bundle\NtfyBundle\Api;

use PBergman\Ntfy\Api\AsyncPublishResponse;
use PBergman\Ntfy\Api\Client;
use PBergman\Ntfy\Model\PublishParameters;
use PBergman\Ntfy\Model\SubscribeParameters;

class StaticTopicClient
{
    private string $topic;
    private Client $client;

    public function __construct(string $topic, Client $client)
    {
        $this->client = $client;
        $this->topic  = $topic;
    }

    public function subscribe(SubscribeParameters $params): \Generator
    {
        foreach ($this->client->subscribe($this->topic, $params) as $message) {
            yield $message;
        }
    }

    public function publish(?PublishParameters $params = null, $body = null): AsyncPublishResponse
    {
        return $this->client->publish($this->topic, $params, $body);
    }
}
