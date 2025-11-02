<?php

declare(strict_types=1);

namespace Forumify\Discord\Exception;

class NoBotRegisteredException extends DiscordBotException
{
    public function __construct()
    {
        parent::__construct("Discord bot has not been registered yet.");
    }
}
