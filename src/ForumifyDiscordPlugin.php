<?php

declare(strict_types=1);

namespace Forumify\Discord;

use Forumify\Plugin\AbstractForumifyPlugin;
use Forumify\Plugin\PluginMetadata;

class ForumifyDiscordPlugin extends AbstractForumifyPlugin
{
    public function getPluginMetadata(): PluginMetadata
    {
        return new PluginMetadata(
            'Discord',
            'forumify',
            'Tightly couple your forumify instance with discord.',
            'https://forumify.net',
        );
    }
}
