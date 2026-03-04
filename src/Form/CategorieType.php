<?php

namespace App\Form;
use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategorieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la catégorie *',
                'attr'  => [
                    'placeholder' => 'Ex: Appartement, Villa, Studio...',
                    'class'       => 'form-control',
                ],
            ])

            ->add('description', TextareaType::class, [
                'label'    => 'Description',
                'required' => false,
                'attr'     => [
                    'placeholder' => 'Décrivez cette catégorie de logements...',
                    'class'       => 'form-control',
                    'rows'        => 4,
                ],
            ])

            ->add('icone', FileType::class, [
                'label'    => 'Icône de la catégorie',
                'required' => false,
                'mapped'   => false,  // Ne correspond pas directement à une propriété
                'attr'     => [
                    'class'  => 'form-control',
                    'accept' => 'image/*',
                ],
                'help' => 'Formats acceptés : JPG, PNG, SVG, WEBP',
            ])

            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer la catégorie',
                'attr'  => ['class' => 'btn btn-primary'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Categorie::class,
        ]);
    }
}
