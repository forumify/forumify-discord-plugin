<?php

declare(strict_types=1);

namespace Forumify\Discord\Service;

use Forumify\Core\Entity\Role;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Api\Resource\RolesChanged;
use Forumify\Discord\Api\Resource\UsernameChanged;
use Forumify\Discord\Exception\DiscordBotException;
use Forumify\Discord\Exception\NoBotRegisteredException;
use Forumify\OAuth\Entity\OAuthClient;
use Forumify\OAuth\Idp\DiscordIdp;
use Forumify\OAuth\Repository\IdentityProviderUserRepository;
use Forumify\OAuth\Repository\OAuthClientRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
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
        private readonly IdentityProviderUserRepository $idpUserRepository,
    ) {
    }

    /**
     * @throws DiscordBotException
     */
    public function sendData(mixed $payload): void
    {
        try {
            $this->getClient()->post('/data', [
                'body' => $this->serializer->serialize($payload, 'jsonld'),
            ]);
        } catch (GuzzleException $ex) {
            throw new DiscordBotException('Unable to send data to bot.', previous: $ex);
        }
    }

    /**
     * @param array<string, mixed> $args
     * @return array<mixed>
     *
     * @throws DiscordBotException
     */
    public function fetchData(string $type, array $args = []): array
    {
        $args['type'] = $type;
        $qs = http_build_query($args);

        try {
            $body = $this->getClient()->get("/data?$qs")->getBody()->getContents();
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException | JsonException $ex) {
            throw new DiscordBotException('Unable to retrieve data from bot.', previous: $ex);
        }
    }

    public function updateUsername(User $user): void
    {
        if (!$this->settingRepository->get('discord.force_matching_username')) {
            return;
        }

        $idpUsers = $this->idpUserRepository->findByUserAndIdpType($user, DiscordIdp::getType());
        foreach ($idpUsers as $idpUser) {
            $usernameChangedDto = new UsernameChanged();
            $usernameChangedDto->discordIdentifier = $idpUser->getExternalIdentifier();
            $usernameChangedDto->discordUsername = $idpUser->getExternalUsername();
            $usernameChangedDto->newUsername = $user->getDisplayName();
            $this->sendData($usernameChangedDto);
        }
    }

    /**
     * @param array<Role> $added
     * @param array<Role> $removed
     */
    public function updateRoles(User $user, array $added, array $removed): void
    {
        $rolesToSync = $this->getRolesToSync();
        if (empty($rolesToSync)) {
            return;
        }

        $rolesChanged = new RolesChanged();
        foreach ($added as $roleAdded) {
            $discordSnowflakes = $rolesToSync[$roleAdded->getId()] ?? [];
            foreach ($discordSnowflakes as $snowflake) {
                $rolesChanged->rolesAdded[] = $snowflake;
            }
        }

        foreach ($removed as $roleRemoved) {
            $discordSnowflakes = $rolesToSync[$roleRemoved->getId()] ?? [];
            foreach ($discordSnowflakes as $snowflake) {
                $rolesChanged->rolesRemoved[] = $snowflake;
            }
        }

        if (empty($rolesChanged->rolesAdded) && empty($rolesChanged->rolesRemoved)) {
            return;
        }

        $idpUsers = $this->idpUserRepository->findByUserAndIdpType($user, DiscordIdp::getType());
        foreach ($idpUsers as $idpUser) {
            $userRolesChanged = clone $rolesChanged;
            $userRolesChanged->discordIdentifier = $idpUser->getExternalIdentifier();
            $this->sendData($userRolesChanged);
        }
    }

    /**
     * @return array<int, array<string>> [forumifyRoleId => [discordRoleSnowflake]]
     */
    private function getRolesToSync(): array
    {
        $rolesToSync = $this->settingRepository->get('discord.sync_roles');
        if (empty($rolesToSync) || !is_array($rolesToSync)) {
            return [];
        }

        $roleMap = [];
        foreach ($rolesToSync as $toSync) {
            if (empty($toSync['discord_role']) || empty($toSync['forumify_role'])) {
                continue;
            }

            $roleMap[$toSync['forumify_role']][] = $toSync['discord_role'];
        }

        return $roleMap;
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
