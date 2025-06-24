<?php

namespace App\Form;

use App\Entity\Notices;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoticesForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('legal_notices', TextareaType::class, [
                'label' => 'Mentions légales'
            ])
            ->add('privacy_policy', TextareaType::class, [
                'label' => 'Politique de confidentialité'
            ])
            ->add('gcu', TextareaType::class, [
                'label' => 'Conditions Générales d\'Utilisation'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notices::class,
        ]);
    }
}
