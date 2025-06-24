<?php

namespace App\Form;

use App\Entity\Order;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderDateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', DateType::class, [
                'attr' => ['min' => (new \DateTime('+7 days')->format('Y-m-d'))]
            ])

            ->add('orderItems', CollectionType::class, [
                'entry_type' => OrderItemQuantityForm::class,
                'allow_add' => false,
                'allow_delete' => false,
                'by_reference' => false,
            ])

            ->add('submit', SubmitType::class, [
                'label' => 'Mettre à jour la commande'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
