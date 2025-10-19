<?php

declare(strict_types=1);

namespace Forumify\Discord\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Forumify\Core\Repository\SettingRepository;
use Forumify\Discord\Api\Resource\DiscordRegistration;

class RegistrationProcessor implements ProcessorInterface
{
    public function __construct(private readonly SettingRepository $settingRepository)
    {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ) {
        if (!$data instanceof DiscordRegistration) {
            return;
        }

        $this->settingRepository->setBulk([
            'discord.endpoint' => $data->endpoint,
            'discord.token' => $data->token,
        ]);
    }
}
