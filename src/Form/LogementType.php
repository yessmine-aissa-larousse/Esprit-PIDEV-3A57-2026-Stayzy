<?php

namespace App\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\Logement;
use App\Entity\Categorie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Positive;
use Symfony\Component\Validator\Constraints\NotBlank;


class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field_name')
            // ── Informations de base ─────────────────────────────────────
            ->add('titre', TextType::class, [
                'label'    => 'Titre du logement *',
                'attr'     => [
                    'placeholder' => 'Ex: Appartement cosy au centre-ville',
                    'class'       => 'form-control',
                ],
                // Les contraintes Assert de l'entité s'appliquent automatiquement
                // On peut aussi en ajouter ici si besoin
            ])

            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Décrivez les caractéristiques du logement...',
                    'class'       => 'form-control',
                    'rows'        => 4,
                ],
            ])

            ->add('categorie', EntityType::class, [
                'label'        => 'Catégorie *',
                'class'        => Categorie::class,
                'choice_label' => 'nom',       // Affiche le nom de la catégorie
                'placeholder'  => 'Choisir une catégorie...',
                'attr'         => ['class' => 'form-select'],
            ])

            // ── Caractéristiques ─────────────────────────────────────────
            ->add('prix', NumberType::class, [
                'label' => 'Prix par nuit (€) *',
                'scale' => 2,                  // 2 décimales
                'attr'  => [
                    'placeholder' => '150.00',
                    'class'       => 'form-control',
                    'step'        => '0.01',
                ],
            ])

            ->add('superficie', IntegerType::class, [
                'label' => 'Superficie (m²) *',
                'attr'  => [
                    'placeholder' => '75',
                    'class'       => 'form-control',
                ],
            ])

            ->add('nombreChambres', IntegerType::class, [
                'label' => 'Chambres *',
                'attr'  => [
                    'placeholder' => '2',
                    'class'       => 'form-control',
                ],
            ])

            ->add('nombreSalleDeBain', IntegerType::class, [
                'label' => 'Salles de bain *',
                'attr'  => [
                    'placeholder' => '1',
                    'class'       => 'form-control',
                ],
            ])

            // ── Aménités (champ JSON → ChoiceType multiple) ──────────────
            ->add('amenites', ChoiceType::class, [
                'label'    => 'Aménités',
                'required' => false,
                'expanded' => true,   // → affiche des checkboxes
                'multiple' => true,   // → permet plusieurs sélections
                'choices'  => [
                    'WiFi'             => 'wifi',
                    'Parking'          => 'parking',
                    'Climatisation'    => 'climatisation',
                    'Piscine'          => 'piscine',
                    'Cuisine équipée'  => 'cuisine',
                    'Lave-linge'       => 'lave_linge',
                    'Balcon/Terrasse'  => 'balcon',
                    'Jardin'           => 'jardin',
                ],
                'attr' => ['class' => 'row'],
            ])

            ->add('disponible', CheckboxType::class, [
                'label'    => 'Logement disponible à la location',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])

            // ── Photos (gérées séparément car non mappées à l'entité) ────
            ->add('photoPrincipale', FileType::class, [
                'label'    => 'Photo principale',
                'required' => false,
                'mapped'   => false,   // ← Ne correspond PAS à une propriété de Logement
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'Formats acceptés : JPG, PNG, WEBP',
            ])

            ->add('photos', FileType::class, [
                'label'    => 'Photos supplémentaires',
                'required' => false,
                'mapped'   => false,   // ← Ne correspond pas directement (on gère manuellement)
                'multiple' => true,    // ← Plusieurs fichiers
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'Vous pouvez sélectionner plusieurs images à la fois',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer le logement',
                'attr'  => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Lie ce formulaire à l'entité Logement
            // → handleRequest() va remplir l'entité automatiquement
            // → isValid() va vérifier les Assert de l'entité automatiquement
            'data_class' => Logement::class,
        ]);
    }
}
