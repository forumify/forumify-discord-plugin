<?php

declare(strict_types=1);

namespace Forumify\Discord\Discord;

use Forumify\Discord\Api\DTO\DiscordCommandResult;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Forumify\Discord\Api\DTO\DiscordCommandOption;

#[AutoconfigureTag('discord.command')]
interface DiscordCommandInterface
{
    public function getName(): string;

    public function getDescription(): string;

    /**
     * @return list<DiscordCommandOption>
     */
    public function getOptions(): array;

    /**
     * @param array<string, mixed> $options
     */
    public function run(array $options): DiscordCommandResult;
}
