<?php

declare(strict_types=1);

namespace Forumify\Discord\Exception;

use RuntimeException;

class NoBotRegisteredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct("Discord bot has not been registered yet.");
    }
}
