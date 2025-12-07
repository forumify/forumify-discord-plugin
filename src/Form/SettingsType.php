<?php

declare(strict_types=1);

namespace Forumify\Discord\Form;

use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Repository\CalendarRepository;
use Forumify\Core\Repository\RoleRepository;
use Forumify\Discord\Service\BotService;
use Forumify\OAuth\Idp\DiscordIdp;
use Forumify\OAuth\Repository\IdentityProviderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
class SettingsType extends AbstractType
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly IdentityProviderRepository $idpRepository,
        private readonly RoleRepository $roleRepository,
        private readonly BotService $botService,
        private readonly ?CalendarRepository $calendarRepository = null,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hasDiscordIdp = $this->idpRepository->count(['type' => DiscordIdp::getType()]) > 0;
        $idpLink = $this->urlGenerator->generate('forumify_admin_identity_providers_list');

        $builder->add('discord__invite_link', TextType::class, [
            'label' => 'Discord Invite Link',
            'help' => 'On Discord, next to the channel you want users to join, click the <i class="ph ph-user-plus"></i> icon. Edit the invite link, and set it to never expire, and no usage limit. Copy the link and paste it here.',
            'help_html' => true,
        ]);

        if ($this->calendarRepository !== null) {
            $builder->add('discord__calendars', ChoiceType::class, [
                'label' => 'Sync Calendar with Discord',
                'help' => 'Events in these calenders will be cross-posted to Discord. Leave blank to disable this feature.',
                'multiple' => true,
                'autocomplete' => true,
                'required' => false,
                'choices' => $this->getCalendarChoices(),
                'placeholder' => '',
            ]);
        }

        $builder
            ->add('discord__force_connect_account', CheckboxType::class, [
                'label' => 'Force users to connect a Discord account',
                'help' => !$hasDiscordIdp
                    ? "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work."
                    : null,
                'help_html' => true,
                'required' => false,
                'disabled' => !$hasDiscordIdp,
            ])
            ->add('discord__force_user_in_server', CheckboxType::class, [
                'label' => 'Force users to join your Discord server',
                'help' => !$hasDiscordIdp
                    ? "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work."
                    : null,
                'help_html' => true,
                'required' => false,
                'disabled' => !$hasDiscordIdp,
            ])
            ->add('discord__force_matching_username', CheckboxType::class, [
                'label' => 'Sync forum display names to Discord',
                'help' => !$hasDiscordIdp
                    ? "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work."
                    : 'Enabling this option will trigger a background task to sync display names for all users. Depending on the size of your community, this may take some time to complete.',
                'help_html' => true,
                'required' => false,
                'disabled' => !$hasDiscordIdp,
            ])
        ;

        if ($hasDiscordIdp) {
            $roleChoices = [];
            $selectableRoles = $this->roleRepository->findBy(['system' => false], ['position' => 'DESC']);
            foreach ($selectableRoles as $role) {
                $roleChoices[$role->getTitle()] = $role->getId();
            }

            $discordRoleChoices = [];
            foreach ($this->botService->fetchData('roles') as $role) {
                if ($role['name'] === '@everyone') {
                    continue;
                }

                $discordRoleChoices[$role['name']] = $role['id'];
            }

            $builder->add('discord__sync_roles', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'required' => false,
                'label' => 'Role Mapping',
                'entry_options' => [
                    'forumify_roles' => $roleChoices,
                    'discord_roles' => $discordRoleChoices,
                ],
                'entry_type' => DiscordRoleMappingType::class,
            ]);
        } else {
            $builder->add('discord__sync_roles', CheckboxType::class, [
                'required' => false,
                'label' => 'Sync Roles',
                'help' => "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work.",
                'disabled' => true,
            ]);
        }
    }

    /**
     * @return array<string, int>
     */
    private function getCalendarChoices(): array
    {
        $choices = ['All Calendars' => '*'];
        $calendars = $this->calendarRepository->findAll();
        /** @var Calendar $calendar */
        foreach ($calendars as $calendar) {
            $choices[$calendar->getTitle()] = $calendar->getId();
        }

        return $choices;
    }
}
