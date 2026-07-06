<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de contact public. Les données ne sont pas persistées : le
 * contrôleur construit un e-mail à destination de l'éditeur (admin@genealia.fr)
 * avec l'adresse saisie en Reply-To. CSRF activé par défaut via form_start.
 */
class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = 'w-full p-3 rounded-sm border border-brand-hairline focus:ring-1 focus:ring-brand-primary outline-none text-sm leading-relaxed';

        $builder
            ->add('name', TextType::class, [
                'label' => 'Votre nom',
                'required' => true,
                'attr' => ['class' => $inputClass, 'placeholder' => 'Prénom Nom'],
                'constraints' => [
                    new NotBlank(message: "Merci d'indiquer votre nom."),
                    new Length(max: 100, maxMessage: '100 caractères maximum.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Votre e-mail',
                'required' => true,
                'attr' => ['class' => $inputClass, 'placeholder' => 'vous@exemple.fr'],
                'constraints' => [
                    new NotBlank(message: "Merci d'indiquer votre e-mail."),
                    new Email(message: "L'adresse e-mail n'est pas valide."),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'required' => true,
                'attr' => [
                    'rows' => 6,
                    'class' => $inputClass,
                    'placeholder' => 'Décrivez votre demande…',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le message ne peut pas être vide.'),
                    new Length(max: 3000, maxMessage: '3000 caractères maximum.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
        ]);
    }
}