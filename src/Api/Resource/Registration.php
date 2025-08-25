<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\Resource;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Forumify\Discord\Api\Processor\RegistrationProcessor;

#[ApiResource(
    shortName: 'DiscordRegistration',
    operations: [
        new Post('/discord/register-bot', processor: RegistrationProcessor::class),
    ]
)]
class Registration
{
    #[ApiProperty(identifier: true)]
    public readonly int $id;
    public string $endpoint;
    public string $token;

    public function __construct()
    {
        $this->id = time();
    }
}
