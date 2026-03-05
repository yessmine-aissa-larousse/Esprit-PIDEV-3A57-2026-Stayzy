<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Votre commentaire'],
                'constraints' => [
                    new NotBlank(['message' => 'Le contenu est obligatoire']),
                    new Length(['min' => 3, 'max' => 2000]),
                ],
            ])
            ->add('author', TextType::class, [
                'label' => 'Auteur',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom'],
                'constraints' => [
                    new NotBlank(['message' => 'L\'auteur est obligatoire']),
                    new Length(['max' => 180]),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Image du commentaire (optionnel)',
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'form-control', 'accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG, PNG, GIF, WebP)',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'comment_type';
    }
}
