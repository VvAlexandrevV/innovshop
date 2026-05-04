<?php

namespace App\Form;

use App\Entity\User;
use PhpParser\Node\Stmt\Label;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserProfileType extends AbstractType
{
    /**
     * Construit le formulaire de modification du profil utilisateur.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Modification des informations personnelles.
     *
     * Cette méthode permet à l'utilisateur connecté de modifier :
     * - son nom
     * - son prénom
     * - son téléphone
     * - son email
     * - son adresse de livraison
     * - son pays
     * - son mot de passe, seulement s’il remplit les deux champs
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /**
             * Champ du nom utilisateur.
             */
            ->add('firstname', null, ['label' => 'Nom'])

            /**
             * Champ du prénom utilisateur.
             */
            ->add('lastname', null, ['label' => 'prénom'])

            /**
             * Champ téléphone.
             *
             * required => false permet de rendre ce champ facultatif.
             */
            ->add('phone', TelType::class, [
                'required' => false,
                'label' => 'Téléphone'
            ])

            /**
             * Champ email.
             *
             * Ce champ est lié à l'entité User.
             */
            ->add('email', null, ['label' => 'Email'])

            /**
             * Champ adresse.
             */
            ->add('address', null, ['label' => 'Adresse'])

            /**
             * Champ code postal.
             */
            ->add('postalCode', null, ['label' => 'Code postal'])

            /**
             * Champ ville.
             */
            ->add('city', null, ['label' => 'Ville'])

            /**
             * Champ pays.
             *
             * CountryType affiche une liste de pays.
             * preferred_choices => ['FR'] place la France en choix prioritaire.
             */
            ->add('country', CountryType::class, [
                'required' => false,
                'label' => 'Pays',
                'preferred_choices' => ['FR'],
                'placeholder' => 'Choisir un pays',
            ])

            /**
             * Champ de modification du mot de passe.
             *
             * Fonctionnalité InnovShop :
             * Espace client - Modification sécurisée du mot de passe.
             *
             * RepeatedType crée deux champs :
             * - nouveau mot de passe
             * - confirmation du nouveau mot de passe
             *
             * Si les deux champs sont différents, Symfony bloque le formulaire.
             *
             * mapped => false car ce champ n'existe pas directement dans l'entité User.
             * Le mot de passe doit être récupéré dans le controller,
             * hashé, puis enregistré dans la propriété password.
             *
             * required => false permet à l'utilisateur de modifier son profil
             * sans être obligé de changer son mot de passe.
             */
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                    'required' => false,
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le nouveau mot de passe',
                    'required' => false,
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    /**
     * Configure les options générales du formulaire.
     *
     * Fonctionnalité InnovShop :
     * Espace client - Formulaire lié au compte utilisateur.
     *
     * data_class indique que ce formulaire est lié à l'entité User.
     * Les champs mapped => false sont ignorés par Doctrine
     * et doivent être traités manuellement dans le controller.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}