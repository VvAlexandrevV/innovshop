<?php

namespace App\Form;

use App\Entity\SellerProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class SellerProfileType extends AbstractType
{
    /**
     * Construit le formulaire de modification du profil vendeur.
     *
     * Fonctionnalité InnovShop :
     * Espace vendeur - Modification des informations entreprise.
     *
     * Ce formulaire ne contient volontairement que les champs
     * que le vendeur a le droit de modifier.
     *
     * Champs modifiables :
     * - email entreprise
     * - téléphone entreprise
     * - adresse entreprise
     * - code postal
     * - ville
     * - pays
     *
     * Champs non présents dans ce formulaire :
     * - companyName
     * - siret
     * - status
     * - stripeAccountId
     * - user
     *
     * Pourquoi ?
     * Parce que ces champs sont sensibles.
     * Le nom officiel et le SIRET représentent l'identité légale du vendeur.
     * Le vendeur ne doit donc pas pouvoir les modifier librement.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            /**
             * Email professionnel de l'entreprise.
             *
             * Cet email est différent de l'email de connexion du User.
             * Il sert à représenter l'entreprise côté marketplace.
             */
            ->add('companyEmail', EmailType::class, [
                'label' => 'Email entreprise',
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
             * Téléphone professionnel de l'entreprise.
             *
             * Champ facultatif.
             */
            ->add('companyPhone', TextType::class, [
                'label' => 'Téléphone entreprise',
                'required' => false,
            ])

            /**
             * Adresse professionnelle de l'entreprise.
             */
            ->add('companyAddress', TextType::class, [
                'label' => 'Adresse entreprise',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir une adresse entreprise.',
                    ),
                ],
            ])

            /**
             * Code postal de l'entreprise.
             */
            ->add('companyPostalCode', TextType::class, [
                'label' => 'Code postal',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un code postal.',
                    ),
                ],
            ])

            /**
             * Ville de l'entreprise.
             */
            ->add('companyCity', TextType::class, [
                'label' => 'Ville',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir une ville.',
                    ),
                ],
            ])

            /**
             * Pays de l'entreprise.
             */
            ->add('companyCountry', TextType::class, [
                'label' => 'Pays',
                'constraints' => [
                    new NotBlank(
                        message: 'Veuillez saisir un pays.',
                    ),
                ],
            ])
        ;
    }

    /**
     * Configure les options générales du formulaire.
     *
     * data_class => SellerProfile::class :
     * indique que ce formulaire est lié à l'entité SellerProfile.
     *
     * Symfony pourra donc remplir automatiquement les champs présents
     * dans cette entité, uniquement ceux déclarés dans buildForm().
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SellerProfile::class,
        ]);
    }
}