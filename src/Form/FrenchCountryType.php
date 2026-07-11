<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Intl\Countries;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Sélecteur de pays aux libellés français, stockant le code ISO 2 lettres.
 *
 * On étend ChoiceType (plutôt que CountryType) car CountryType construit sa
 * liste via Countries::getNames($locale), qui trie les pays avec un Collator
 * ICU — or l'extension PHP « intl » n'est pas obligatoirement installée et le
 * polyfill ne supporte que la locale « en ». Countries::getName() (singulier)
 * fonctionne sans intl : on construit donc les choix soi-même, libellé français
 * → code ISO, triés alphabétiquement.
 */
class FrenchCountryType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'choices' => self::frenchChoices(),
            'choice_translation_domain' => false,
            'preferred_choices' => ['FR'],
            'placeholder' => '— Pays —',
            'required' => false,
        ]);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * @return array<string, string> libellé français => code ISO 2 lettres
     */
    public static function frenchChoices(): array
    {
        $choices = [];
        foreach (Countries::getCountryCodes() as $code) {
            $choices[Countries::getName($code, 'fr')] = $code;
        }

        $keys = array_keys($choices);
        $values = array_values($choices);
        array_multisort($keys, SORT_ASC, SORT_STRING, $values);

        return array_combine($keys, $values);
    }
}