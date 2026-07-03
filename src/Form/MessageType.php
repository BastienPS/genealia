<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de rédaction d'un message. Réutilisé côté admin et côté
 * client : le contenu est le seul champ lié ; isFromAdmin / conversation /
 * authorLabel sont positionnés par le contrôleur (pas de mass-assignment).
 *
 * Le rendu via form_start/form_end active automatiquement la protection CSRF
 * (contrairement au formulaire « status » à la main de l'admin) — c'est le
 * motif à suivre pour tout POST de la messagerie.
 */
class MessageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => $options['label'] ?? 'Votre message',
                'mapped' => false,
                'required' => true,
                'attr' => [
                    'rows' => 4,
                    'class' => 'w-full p-3 rounded-sm border border-brand-hairline focus:ring-1 focus:ring-brand-primary outline-none text-sm leading-relaxed',
                    'placeholder' => 'Écrivez votre message…',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le message ne peut pas être vide.'),
                    new Length(max: 2000, maxMessage: '2000 caractères maximum.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // CSRF activé par défaut (symfony/form) — rendu via form_end.
            'csrf_protection' => true,
            // Libellé du champ adapté selon l'expéditeur (admin vs client).
            'label' => 'Votre message',
        ]);
    }
}