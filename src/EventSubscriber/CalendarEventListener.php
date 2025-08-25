<?php

declare(strict_types=1);

namespace Forumify\Discord\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Calendar\Entity\CalendarEvent;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Service\BotService;

#[AsEntityListener(Events::postPersist, method: 'postPersist', entity: CalendarEvent::class)]
class CalendarEventListener
{
    public function __construct(
        private readonly BotService $botService,
        private readonly SettingRepository $settingRepository,
    ) {
    }

    public function postPersist(CalendarEvent $event): void
    {
        $calendarsToSync = $this->settingRepository->get('discord.calendars');
        if (empty($calendarsToSync)) {
            return;
        }

        if (!in_array($event->getCalendar()->getId(), $calendarsToSync)) {
            return;
        }

        $this->botService->sendToBot($event);
    }
}
