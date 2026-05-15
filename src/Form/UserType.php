<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr'  => ['class' => 'form-control', 'placeholder' => 'exemple@email.com', 'id' => 'field_email'],
                'constraints' => [
                    new NotBlank(['message' => '❌ Email est obligatoire']),
                    new Length(['max' => 100, 'maxMessage' => '❌ Email trop long (max 100 caractères)']),
                    new Email(['message' => '❌ Format email invalide']),
                ],
            ])

            ->add('password', PasswordType::class, [
                'label'    => $isEdit ? 'Nouveau mot de passe (laisser vide pour garder l\'ancien)' : 'Mot de passe',
                'mapped'   => false,
                'required' => !$isEdit,
                'attr'     => ['class' => 'form-control', 'id' => 'field_password'],
                'constraints' => $isEdit ? [] : [
                    new NotBlank(['message' => '❌ Mot de passe est obligatoire']),
                    new Length([
                        'min'        => 8,
                        'max'        => 50,
                        'minMessage' => '❌ Mot de passe doit contenir au moins 8 caractères',
                        'maxMessage' => '❌ Mot de passe trop long (max 50 caractères)',
                    ]),
                    new Regex([
                        'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/',
                        'message' => '❌ Mot de passe faible. Doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.',
                    ]),
                ],
            ])

            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'attr'  => ['class' => 'form-control', 'id' => 'field_nom'],
                'constraints' => [
                    new NotBlank(['message' => '❌ Nom est obligatoire']),
                    new Length([
                        'min' => 2, 'max' => 50,
                        'minMessage' => '❌ Nom doit contenir au moins 2 caractères',
                        'maxMessage' => '❌ Nom trop long (max 50 caractères)',
                    ]),
                    ...(!$isEdit ? [new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/',
                        'message' => '❌ Nom : lettres, espaces, tirets et apostrophes uniquement',
                    ])] : []),
                ],
            ])

            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'attr'  => ['class' => 'form-control', 'id' => 'field_prenom'],
                'constraints' => [
                    new NotBlank(['message' => '❌ Prénom est obligatoire']),
                    new Length([
                        'min' => 2, 'max' => 50,
                        'minMessage' => '❌ Prénom doit contenir au moins 2 caractères',
                        'maxMessage' => '❌ Prénom trop long (max 50 caractères)',
                    ]),
                    ...(!$isEdit ? [new Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]{2,50}$/',
                        'message' => '❌ Prénom : lettres, espaces, tirets et apostrophes uniquement',
                    ])] : []),
                ],
            ])

            ->add('tel', TextType::class, [
                'label'      => 'Téléphone',
                'required'   => false,
                'empty_data' => null,
                'attr'       => ['class' => 'form-control', 'placeholder' => '+216 XX XXX XXX', 'id' => 'field_tel'],
                'constraints' => !$isEdit ? [
                    new Regex([
                        'pattern' => '/^\+?216?[0-9]{8}$|^\+?[0-9]{10,15}$/',
                        'message' => '❌ Téléphone invalide (format: +216 XX XXX XXX ou international)',
                    ]),
                ] : [],
            ])

            ->add('adresse', TextType::class, [
                'label'      => 'Adresse',
                'required'   => false,
                'empty_data' => null,
                'attr'       => ['class' => 'form-control', 'id' => 'field_adresse'],
                'constraints' => !$isEdit ? [
                    new Length([
                        'min' => 5, 'max' => 100,
                        'minMessage' => '❌ Adresse trop courte (min 5 caractères)',
                        'maxMessage' => '❌ Adresse trop longue (max 100 caractères)',
                    ]),
                ] : [
                    new Length([
                        'max' => 100,
                        'maxMessage' => '❌ Adresse trop longue (max 100 caractères)',
                    ]),
                ],
            ])

            ->add('imageUrl', TextType::class, [
                'label'    => 'URL de l\'image',
                'required' => false,
                'attr'     => ['class' => 'form-control', 'placeholder' => 'https://exemple.com/image.jpg'],
            ])

            ->add('roles', ChoiceType::class, [
                'label'    => 'Rôle(s)',
                'mapped'   => false,
                'choices'  => [
                    'Client'         => 'ROLE_CLIENT',
                    'Propriétaire'   => 'ROLE_PROPRIETAIRE',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'attr'     => ['class' => 'form-check'],
            ])

            ->add('isActive', CheckboxType::class, [
                'label'      => 'Compte actif',
                'required'   => false,
                'attr'       => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit'    => false,
        ]);
    }
}
