<?php

declare(strict_types=1);

namespace Forumify\Discord\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('join', 'join')]
class JoinServerController extends AbstractController
{
    public function __invoke(): Response
    {
        
    }
}
