<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    /**
     * Construit le formulaire d'inscription.
     *
     * Fonctionnalité InnovShop :
     * Front Office - Création de compte.
     *
     * Ce formulaire fonctionne maintenant avec deux modes :
     * - individual : formulaire particulier
     * - company : formulaire entreprise / vendeur
     *
     * Le mode est envoyé depuis RegistrationController via l'option "account_type".
     *
     * Avantage :
     * on n'affiche plus un faux formulaire entreprise quand l'utilisateur
     * est en train de créer un compte particulier.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Type de compte demandé.
         *
         * Valeurs possibles :
         * - individual
         * - company
         */
        $accountType = $options['account_type'];

        $builder
            /**
             * Champ caché contenant le type de compte.
             *
             * Pourquoi ?
             * Parce que quand le formulaire est envoyé en POST,
             * on veut savoir si l'utilisateur crée :
             * - un compte particulier
             * - un compte entreprise
             *
             * mapped => false :
             * ce champ n'existe pas dans l'entité User.
             */
            ->add('accountType', HiddenType::class, [
                'mapped' => false,
                'data' => $accountType,
            ])

            /**
             * Email du compte utilisateur.
             *
             * Cet email sert à la connexion.
             * Il est stocké dans User::$email.
             */
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir une adresse email.',
                    ),
                    new Email(
                        message: 'Veuillez saisir une adresse email valide.',
                    ),
                ],
            ])

            /**
             * Case d'acceptation des conditions.
             *
             * mapped => false :
             * on ne stocke pas cette case dans User.
             * Elle sert uniquement à valider l'inscription.
             */
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J’accepte les conditions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les conditions.',
                    ),
                ],
            ])

            /**
             * Mot de passe avec confirmation.
             *
             * RepeatedType crée deux champs :
             * - plainPassword.first
             * - plainPassword.second
             *
             * mapped => false :
             * on ne stocke jamais le mot de passe en clair dans User.
             * Le controller le récupère, le hash, puis enregistre uniquement le hash.
             */
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password',
                    ],
                ],
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez entrer un mot de passe.',
                    ),
                    new Length(
                        min: 6,
                        minMessage: 'Votre mot de passe doit contenir au moins {{ limit }} caractères.',
                        max: 4096,
                    ),
                ],
            ])
        ;

        /**
         * Champs entreprise.
         *
         * Ces champs ne sont ajoutés que si le formulaire est en mode "company".
         *
         * Résultat :
         * - formulaire particulier : aucun champ entreprise
         * - formulaire entreprise : champs entreprise visibles et obligatoires
         */
        if ($accountType === 'company') {
            $builder
                /**
                 * Nom officiel de l'entreprise.
                 *
                 * mapped => false :
                 * ce champ ira dans SellerProfile, pas dans User.
                 */
                ->add('companyName', TextType::class, [
                    'label' => 'Nom de l’entreprise',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir le nom de votre entreprise.',
                        ),
                    ],
                ])

                /**
                 * SIRET de l'entreprise.
                 *
                 * Un SIRET français contient exactement 14 chiffres.
                 */
                ->add('siret', TextType::class, [
                    'label' => 'SIRET',
                    'mapped' => false,
                    'attr' => [
                        'maxlength' => 14,
                    ],
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir votre SIRET.',
                        ),
                        new Regex(
                            pattern: '/^\d{14}$/',
                            message: 'Le SIRET doit contenir exactement 14 chiffres.',
                        ),
                    ],
                ])

                /**
                 * Email professionnel de l'entreprise.
                 */
                ->add('companyEmail', EmailType::class, [
                    'label' => 'Email entreprise',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir un email entreprise.',
                        ),
                        new Email(
                            message: 'Veuillez saisir un email entreprise valide.',
                        ),
                    ],
                ])

                /**
                 * Téléphone professionnel.
                 *
                 * Champ facultatif.
                 */
                ->add('companyPhone', TextType::class, [
                    'label' => 'Téléphone entreprise',
                    'mapped' => false,
                    'required' => false,
                ])

                /**
                 * Adresse professionnelle.
                 */
                ->add('companyAddress', TextType::class, [
                    'label' => 'Adresse entreprise',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir l’adresse de votre entreprise.',
                        ),
                    ],
                ])

                /**
                 * Code postal de l'entreprise.
                 */
                ->add('companyPostalCode', TextType::class, [
                    'label' => 'Code postal entreprise',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir le code postal de votre entreprise.',
                        ),
                    ],
                ])

                /**
                 * Ville de l'entreprise.
                 */
                ->add('companyCity', TextType::class, [
                    'label' => 'Ville entreprise',
                    'mapped' => false,
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir la ville de votre entreprise.',
                        ),
                    ],
                ])

                /**
                 * Pays de l'entreprise.
                 */
                ->add('companyCountry', TextType::class, [
                    'label' => 'Pays entreprise',
                    'mapped' => false,
                    'data' => 'France',
                    'constraints' => [
                        new NotBlank(
                            message: 'Veuillez saisir le pays de votre entreprise.',
                        ),
                    ],
                ])
            ;
        }
    }

    /**
     * Configure les options générales du formulaire.
     *
     * data_class => User::class :
     * le formulaire principal remplit un objet User.
     *
     * account_type :
     * option personnalisée qui permet de savoir quel formulaire afficher.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'account_type' => 'individual',
        ]);

        /**
         * On limite les valeurs autorisées pour éviter les types inconnus.
         */
        $resolver->setAllowedValues('account_type', [
            'individual',
            'company',
        ]);
    }
}