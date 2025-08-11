<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\Controller;

use Forumify\Plugin\Attribute\PluginVersion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[PluginVersion('forumify/forumify-discord-plugin', 'regular')]
#[IsGranted('ROLE_OAUTH_CLIENT')]
class ConnectController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $payload = $request->getPayload();
    }
}
