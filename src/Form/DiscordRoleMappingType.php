<?php

declare(strict_types=1);

namespace Forumify\Discord\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscordRoleMappingType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'forumify_roles' => [],
            'discord_roles' => [],
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('forumify_role', ChoiceType::class, [
                'choices' => $options['forumify_roles'],
                'label' => false,
            ])
            ->add('discord_role', ChoiceType::class, [
                'choices' => $options['discord_roles'],
                'label' => false,
            ])
        ;
    }
}
