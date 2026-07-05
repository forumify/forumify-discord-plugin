<?php

declare(strict_types=1);

namespace Forumify\Discord\EventSubscriber;

use Forumify\Discord\ForumifyDiscordPlugin;
use Forumify\Discord\Service\BotService;
use Forumify\Plugin\Event\PluginRefreshedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(PluginRefreshedEvent::class)]
class RefreshFormsAndViewsListener
{
    public function __construct(private readonly BotService $botService)
    {
    }

    public function __invoke(PluginRefreshedEvent $event): void
    {
        if ($event->plugin->getPluginClass() !== ForumifyDiscordPlugin::class) {
            return;
        }

        $this->botService->fetchData('refreshSlashCommands');
    }
}
