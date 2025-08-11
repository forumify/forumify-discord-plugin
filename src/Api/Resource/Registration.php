<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Forumify\Discord\Api\Processor\RegistrationProcessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post('/discord/register', processor: RegistrationProcessor::class),
    ]
)]
class Registration
{
    #[Assert\NotBlank(allowNull: false)]
    #[Groups('Register')]
    public string $endpoint;

    #[Assert\NotBlank(allowNull: false)]
    #[Groups('Register')]
    public string $token;
}
