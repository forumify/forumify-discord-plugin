<?php

declare(strict_types=1);

namespace Forumify\Discord\EventSubscriber;

use Forumify\Core\Entity\User;
use Forumify\Core\Repository\SettingRepository;
use Forumify\OAuth\Entity\IdentityProvider;
use Forumify\OAuth\Entity\IdentityProviderUser;
use Forumify\OAuth\Idp\DiscordIdp;
use Forumify\OAuth\Repository\IdentityProviderRepository;
use Forumify\OAuth\Repository\IdentityProviderUserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: -999)]
class ForceDiscordLoginListener
{
    public function __construct(
        private readonly SettingRepository $settingRepository,
        private readonly IdentityProviderRepository $idpRepository,
        private readonly IdentityProviderUserRepository $idpUserRepository,
        private readonly Security $security,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $requestRoute = $event->getRequest()->attributes->get('_route');
        if ($requestRoute === null
            || $requestRoute === 'discord_connect'
            || $requestRoute === 'ux_live_component'
            || str_starts_with($requestRoute, 'forumify_core_')
            || str_starts_with($requestRoute, 'forumify_oauth_idp_')) {
            return;
        }

        if (!$this->settingRepository->get('discord.force_connect_account')) {
            return;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return;
        }

        /** @var array<IdentityProvider> $discordIdps */
        $discordIdps = $this->idpRepository->findBy(['type' => DiscordIdp::getType()]);
        if (empty($discordIdps)) {
            return;
        }

        /** @var array<IdentityProviderUser> $idpUsers */
        $idpUsers = $this->idpUserRepository->findBy(['user' => $user]);
        foreach ($idpUsers as $idpUser) {
            foreach ($discordIdps as $discordIdp) {
                if ($discordIdp->getId() === $idpUser->getIdentityProvider()->getId()) {
                    return;
                }
            }
        }

        $connectRoute = $this->urlGenerator->generate('discord_connect');
        $event->setResponse(new RedirectResponse($connectRoute));
    }
}
