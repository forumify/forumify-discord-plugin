<?php

declare(strict_types=1);

namespace Forumify\Discord\Controller\Admin;

use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Form\SettingsType;
use Forumify\Discord\Service\BotService;
use Forumify\OAuth\Repository\OAuthClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('settings', 'settings')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly BotService $botService,
        private readonly OAuthClientRepository $clientRepository,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $settingsData = $this->settingRepository->toFormData('discord');
        $form = $this->createForm(SettingsType::class, $settingsData);
        $form->handleRequest($request);

        $client = null;
        $status = $this->botService->healthCheck();
        if ($status === BotService::STATUS_NOT_REGISTERED) {
            $client = $this->botService->getOrCreateOAuthClient();
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $settingsData = $form->getData();
            $this->settingRepository->handleFormData($settingsData);
            $this->addFlash('success', 'Discord settings saved.');
        }

        return $this->render('@ForumifyDiscordPlugin/admin/settings/settings.html.twig', [
            'client' => $client,
            'form' => $form,
            'status' => $status,
        ]);
    }
}
