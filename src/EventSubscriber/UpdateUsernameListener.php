<?php

declare(strict_types=1);

namespace Forumify\Discord\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Service\BotService;

#[AsDoctrineListener(event: Events::onFlush)]
class UpdateUsernameListener
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly BotService $botService,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->settingRepository->get('discord.force_matching_username')) {
            return;
        }

        /** @var EntityManagerInterface $em */
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof User) {
                continue;
            }

            $changeset = $uow->getEntityChangeSet($entity);
            if (isset($changeset['displayName']) || isset($changeset['username'])) {
                $this->botService->updateUsername($entity);
            }
        }
    }
}
