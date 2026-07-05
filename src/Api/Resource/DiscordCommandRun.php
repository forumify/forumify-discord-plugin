<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Forumify\Discord\Api\Processor\DiscordCommandRunProcessor;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    operations: [new Post(
        uriTemplate: '/discord/commands/run',
        processor: DiscordCommandRunProcessor::class,
        output: DiscordCommandResult::class,
    )]
)]
class DiscordCommandRun
{
    #[Groups('DiscordCommandRun')]
    public string $name;

    /** @var array<string, mixed> */
    #[Groups('DiscordCommandRun')]
    public array $options;

    #[Groups('DiscordCommandRun')]
    public string $discordUserId;
}
