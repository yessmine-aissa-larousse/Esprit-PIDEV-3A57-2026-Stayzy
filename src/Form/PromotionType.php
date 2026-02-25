<?php

namespace App\Form;

use App\Entity\Promotion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PromotionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la promotion *',
                'attr'  => [
                    'placeholder' => 'Ex: Offre spéciale été, Flash Sale...',
                    'class'       => 'form-control',
                ],
            ])

            ->add('pourcentage', IntegerType::class, [
                'label' => 'Réduction (%) *',
                'attr'  => [
                    'placeholder' => '20',
                    'class'       => 'form-control',
                    'min'         => 1,
                    'max'         => 99,
                ],
                
            ])

            ->add('dateDebut', DateTimeType::class, [
                'label'        => 'Date de début *',
                'widget'       => 'single_text',
                'html5'        => true,
                'required'     => true,
                'attr'         => ['class' => 'form-control'],
                'invalid_message' => 'La date de début est invalide.',
            ])

            ->add('dateFin', DateTimeType::class, [
                'label'        => 'Date de fin *',
                'widget'       => 'single_text',
                'html5'        => true,
                'required'     => true,
                'attr'         => ['class' => 'form-control'],
                'invalid_message' => 'La date de fin est invalide.',
            ])

            ->add('codePromo', TextType::class, [
                'label'    => 'Code promo (optionnel)',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Ex: STAYZY20',
                    'class'       => 'form-control',
                    'style'       => 'text-transform:uppercase',
                ],
                'help' => 'Lettres majuscules et chiffres uniquement',
            ])

            ->add('active', CheckboxType::class, [
                'label'    => 'Activer cette promotion immédiatement',
                'required' => false,
                'attr'     => ['class' => 'form-check-input'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Promotion::class]);
    }
}