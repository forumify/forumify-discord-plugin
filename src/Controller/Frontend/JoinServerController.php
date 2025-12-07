<?php

declare(strict_types=1);

namespace Forumify\Discord\Controller\Frontend;

use Forumify\Core\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('join', 'join')]
class JoinServerController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
    ) {
    }

    public function __invoke(): Response
    {
        $inviteLink = $this->settingRepository->get('discord.invite_link');
        if (is_string($inviteLink) && !str_starts_with($inviteLink, 'https://')) {
            $inviteLink = "https://discord.gg/$inviteLink";
        }

        return $this->render('@ForumifyDiscordPlugin/frontend/join.html.twig', [
            'inviteLink' => $inviteLink,
        ]);
    }
}
