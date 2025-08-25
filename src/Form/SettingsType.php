<?php

declare(strict_types=1);

namespace Forumify\Discord\Form;

use Forumify\Calendar\Entity\Calendar;
use Forumify\Calendar\Repository\CalendarRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class SettingsType extends AbstractType
{
    public function __construct(private readonly CalendarRepository $calendarRepository)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('discord__calendars', ChoiceType::class, [
                'autocomplete' => true,
                'choices' => $this->getCalendarChoices(),
                'help' => 'Events in these calenders will also be posted to Discord. Leave blank to disable this feature. This will only impact new events and removing a calendar will not delete any existing Discord events.',
                'label' => 'Calendars to sync',
                'multiple' => true,
                'placeholder' => '',
            ])
        ;
    }

    private function getCalendarChoices(): array
    {
        $choices = [];
        $calendars = $this->calendarRepository->findAll();
        /** @var Calendar $calendar */
        foreach ($calendars as $calendar) {
            $choices[$calendar->getTitle()] = $calendar->getId();
        }

        return $choices;
    }
}
