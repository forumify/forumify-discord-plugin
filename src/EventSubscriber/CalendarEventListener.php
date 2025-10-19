<?php

declare(strict_types=1);

namespace Forumify\Discord\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Forumify\Calendar\Entity\Calendar;
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
        if (!$this->shouldSyncCalendar($event->getCalendar())) {
            return;
        }

        $this->botService->sendToBot($event);
    }

    private function shouldSyncCalendar(Calendar $calendar)
    {
        $calendarsToSync = $this->settingRepository->get('discord.calendars');
        if (empty($calendarsToSync)) {
            return false;
        }

        foreach ($calendarsToSync as $toSyncId) {
            if ($toSyncId === '*') {
                return true;
            }

            if ((int)$toSyncId === $calendar->getId()) {
                return true;
            }
        }
        return false;
    }
}
