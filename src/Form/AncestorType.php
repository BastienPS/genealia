<?php

namespace App\Form;

use App\Entity\Ancestor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AncestorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('gender', ChoiceType::class, [
                'label' => 'Sexe',
                'choices' => [
                    'Homme' => 'homme',
                    'Femme' => 'femme',
                ],
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Marie'],
                'constraints' => [new NotBlank()],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => ['placeholder' => 'Martin'],
                'constraints' => [new NotBlank()],
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['placeholder' => 'jj/mm/aaaa'],
            ])
            ->add('birthPlace', TextType::class, [
                'label' => 'Lieu de naissance',
                'attr' => [
                    'placeholder' => 'Lyon, Rhône',
                    'data-action' => 'input->commune-autocomplete#search keydown->commune-autocomplete#navigate',
                    'data-commune-autocomplete-target' => 'input',
                ],
                'required' => false,
            ])
            ->add('birthCountry', FrenchCountryType::class, [
                'label' => 'Pays de naissance',
            ])
            ->add('deathDate', DateType::class, [
                'label' => 'Date de décès',
                'widget' => 'single_text',
                'required' => false,
                'help' => 'Laisser vide si la personne est vivante ou la date inconnue.',
            ])
            ->add('deathPlace', TextType::class, [
                'label' => 'Lieu de décès',
                'attr' => [
                    'placeholder' => 'Paris, Seine',
                    'data-action' => 'input->commune-autocomplete#search keydown->commune-autocomplete#navigate',
                    'data-commune-autocomplete-target' => 'input',
                ],
                'required' => false,
            ])
            ->add('deathCountry', FrenchCountryType::class, [
                'label' => 'Pays de décès',
            ])
            ->add('marriageDate', DateType::class, [
                'label' => 'Date de mariage',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('marriagePlace', TextType::class, [
                'label' => 'Lieu de mariage',
                'attr' => [
                    'placeholder' => 'Lyon, Rhône',
                    'data-action' => 'input->commune-autocomplete#search keydown->commune-autocomplete#navigate',
                    'data-commune-autocomplete-target' => 'input',
                ],
                'required' => false,
            ])
            ->add('marriageCountry', FrenchCountryType::class, [
                'label' => 'Pays de mariage',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ancestor::class,
        ]);
    }
}