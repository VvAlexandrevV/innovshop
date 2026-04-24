<?php

namespace App\Form;

use App\Entity\User;
use PhpParser\Node\Stmt\Label;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['label' => 'Nom'])
            ->add('lastname', null, ['label' => 'prénom'])
            ->add('phone', TelType::class, [
                'required' => false,'label' => 'Téléphone'])
            ->add('email', null, ['label' => 'Email'])    
            ->add('address', null, ['label' => 'Adresse'])
            ->add('postalCode', null, ['label' => 'Code postal'])
            ->add('city', null, ['label' => 'Ville'])
            ->add('country', CountryType::class, [
                'required' => false, 'label' => 'Pays',
                'preferred_choices' => ['FR'],
                'placeholder' => 'Choisir un pays',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}