<?php

declare(strict_types=1);

namespace Forumify\Discord\Form;

use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Repository\CalendarRepository;
use Forumify\OAuth\Idp\DiscordIdp;
use Forumify\OAuth\Repository\IdentityProviderRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SettingsType extends AbstractType
{
    public function __construct(
        private readonly CalendarRepository $calendarRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly IdentityProviderRepository $idpRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $hasDiscordIdp = $this->idpRepository->count(['type' => DiscordIdp::getType()]) > 0;
        $idpLink = $this->urlGenerator->generate('forumify_admin_identity_providers_list');

        $builder
            ->add('discord__calendars', ChoiceType::class, [
                'label' => 'Sync Calendar with Discord',
                'help' => 'Events in these calenders will also be posted to Discord. Leave blank to disable this feature. This will only impact new events and changing this setting will not delete any existing Discord events.',
                'multiple' => true,
                'autocomplete' => true,
                'choices' => $this->getCalendarChoices(),
                'placeholder' => '',
            ])
            ->add('discord__force_connect_account', CheckboxType::class, [
                'label' => 'Force users to connect a Discord account',
                'help' => $hasDiscordIdp
                    ? null
                    : "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work.",
                'help_html' => true,
                'required' => false,
                'disabled' => !$hasDiscordIdp,
            ])
            ->add('discord__force_matching_username', CheckboxType::class, [
                'label' => 'Sync forum display names to Discord',
                'help' => $hasDiscordIdp
                    ? 'Discord bots can only modify users with roles below their own.'
                    : "You must have Discord added as an <a href='$idpLink'>Identity Provider</a> for this to work.",
                'help_html' => true,
                'required' => false,
                'disabled' => !$hasDiscordIdp,
            ])
        ;
    }

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
