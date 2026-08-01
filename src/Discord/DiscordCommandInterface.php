<?php

declare(strict_types=1);

namespace Forumify\Discord\Discord;

use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Forumify\Discord\Api\DTO\DiscordCommandOption;
use Forumify\Discord\Api\Resource\DiscordCommandRun;

#[AutoconfigureTag('discord.command')]
interface DiscordCommandInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @return list<DiscordCommandOption>
     */
    public function getOptions(): array;

    public function run(DiscordCommandRun $command): DiscordCommandResult;
}
