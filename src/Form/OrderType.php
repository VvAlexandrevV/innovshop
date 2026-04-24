<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('deliveryLastName', null, ['label' => 'Nom'])
            ->add('deliveryFirstName', null, ['label' => 'Prénom'])
            ->add('deliveryAddress', null, ['label' => 'Adresse'])
            ->add('deliveryPostalCode', null, ['label' => 'Code postal'])
            ->add('deliveryCity', null, ['label' => 'Ville'])
            ->add('deliveryCountry', null, ['label' => 'Pays'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
