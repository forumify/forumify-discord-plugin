<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\DTO;

use Symfony\Component\Serializer\Attribute\Groups;

class DiscordCommandResult
{
    #[Groups('DiscordCommandRun')]
    public ?string $content = null;

    /** @var list<DiscordEmbed> */
    #[Groups('DiscordCommandRun')]
    public array $embeds = [];
}
