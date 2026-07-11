<?php

namespace App\Form;

use App\Entity\ResearchRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Bridge\SymfonyFormsExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvents as FormEventsBase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\BannedWords;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;

class ResearchRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('clientName', TextType::class, [
                'label' => 'Votre nom complet',
                'attr' => ['placeholder' => 'Jean Dupont'],
                'constraints' => [new NotBlank()],
            ])
            ->add('clientEmail', EmailType::class, [
                'label' => 'Votre adresse email',
                'attr' => ['placeholder' => 'jean.dupont@example.com'],
                'constraints' => [new NotBlank(), new Email()],
            ])
            ->add('ancestorFirstName', TextType::class, [
                'label' => 'Prénom de l\'ancêtre recherché',
                'attr' => ['placeholder' => 'Marie'],
                'constraints' => [new NotBlank()],
            ])
            ->add('ancestorLastName', TextType::class, [
                'label' => 'Nom de famille de l\'ancêtre',
                'attr' => ['placeholder' => 'Martin'],
                'constraints' => [new NotBlank()],
            ])
            ->add('estimatedBirthDate', TextType::class, [
                'label' => 'Date de naissance approximative',
                'attr' => ['placeholder' => 'Vers 1850'],
                'required' => false,
            ])
            ->add('estimatedBirthPlace', TextType::class, [
                'label' => 'Lieu de naissance approximatif',
                'attr' => [
                    'placeholder' => 'Lyon, Rhône',
                    'data-action' => 'input->commune-autocomplete#search keydown->commune-autocomplete#navigate',
                    'data-commune-autocomplete-target' => 'input',
                ],
                'required' => false,
            ])
            ->add('estimatedBirthCountry', FrenchCountryType::class, [
                'label' => 'Pays de naissance',
            ])
            ->add('estimatedDeathDate', TextType::class, [
                'label' => 'Date de décès approximative',
                'attr' => ['placeholder' => 'Vers 1920'],
                'required' => false,
            ])
            ->add('estimatedDeathPlace', TextType::class, [
                'label' => 'Lieu de décès approximatif',
                'attr' => [
                    'placeholder' => 'Paris, Seine',
                    'data-action' => 'input->commune-autocomplete#search keydown->commune-autocomplete#navigate',
                    'data-commune-autocomplete-target' => 'input',
                ],
                'required' => false,
            ])
            ->add('estimatedDeathCountry', FrenchCountryType::class, [
                'label' => 'Pays de décès',
            ])
            ->add('researchGoals', TextareaType::class, [
                'label' => 'Que souhaitez-vous découvrir précisément ?',
                'attr' => ['placeholder' => 'Ex: Retrouver l\'acte de mariage de mon arrière-grand-père...'],
                'required' => false,
            ])
            ->add('additionalInfo', TextareaType::class, [
                'label' => 'Informations complémentaires',
                'attr' => ['placeholder' => 'Tout détail qui pourrait aider la recherche...'],
                'required' => false,
            ])
            ->add('documents', FileType::class, [
                'label' => 'Documents et indices (optionnel)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'help' => 'Photos d\'actes, arbres généalogiques, etc. PDF, JPEG, PNG ou TIFF, 5 Mo max par fichier.',
                'constraints' => [
                    new All(constraints: [
                        new File(
                            maxSize: '5M',
                            mimeTypes: [
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/tiff',
                            ],
                            mimeTypesMessage: 'Format accepté : PDF, JPEG, PNG ou TIFF.',
                        ),
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResearchRequest::class,
        ]);
    }
}
