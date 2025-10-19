<?php

declare(strict_types=1);

namespace Forumify\Discord\Service;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Exception\NoBotRegisteredException;
use Forumify\OAuth\Entity\OAuthClient;
use Forumify\OAuth\Repository\OAuthClientRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Serializer\SerializerInterface;

class BotService
{
    public const string STATUS_ONLINE = 'online';
    public const string STATUS_OFFLINE = 'offline';
    public const string STATUS_NOT_REGISTERED = 'not-registered';

    private ?Client $client = null;

    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly SerializerInterface $serializer,
        private readonly OAuthClientRepository $oAuthClientRepository,
    ) {
    }

    public function sendToBot(mixed $payload): void
    {
        try {
            $this->getClient()->post('/data', [
                'body' => $this->serializer->serialize($payload, 'jsonld'),
            ]);
        } catch (GuzzleException) {
            // TODO: something with this
        }
    }

    public function healthCheck(): string
    {
        try {
            $this->getClient()->get('/ready');
        } catch (NoBotRegisteredException) {
            return self::STATUS_NOT_REGISTERED;
        } catch (GuzzleException) {
            return self::STATUS_OFFLINE;
        }

        return self::STATUS_ONLINE;
    }

    public function getOrCreateOAuthClient(): OAuthClient
    {
        $clientId = $this->settingRepository->get('discord.oauth_client_id');
        if ($clientId === null) {
            $clientId = $this->generateClientId();
            $this->settingRepository->set('discord.oauth_client_id', $clientId);
        }

        $client = $this->oAuthClientRepository->findOneBy(['clientId' => $clientId]);
        if ($client === null) {
            $client = new OAuthClient();
            $client->setName('Discord Bot');
            $client->setClientId($clientId);
            $this->oAuthClientRepository->save($client);
        }

        return $client;
    }

    private function generateClientId(): string
    {
        $i = 0;
        $desired = 'forumify-discord-bot';
        do {
            $clientId = $desired . ($i === 0 ? '' : "-$i");
            $client = $this->oAuthClientRepository->findOneBy(['clientId' => $clientId]);
            $i++;
        } while ($client !== null);

        return $clientId;
    }

    private function getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $endpoint = $this->settingRepository->get('discord.endpoint');
        $token = $this->settingRepository->get('discord.token');
        if (empty($endpoint) || empty($token)) {
            throw new NoBotRegisteredException();
        }

        $this->client = new Client([
            'base_uri' => $endpoint,
            'headers' => [
                'Authorization' => "Bearer $token",
            ],
        ]);
        return $this->client;
    }
}
