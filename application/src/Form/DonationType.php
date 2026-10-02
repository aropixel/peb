<?php

namespace App\Form;

use App\Form\Model\DonationRequest;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<DonationRequest>
 */
class DonationType extends AbstractType
{
    /**
     * Montants proposés, en euros.
     */
    public const AMOUNTS = [10, 20, 50, 100];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (self::AMOUNTS as $euros) {
            $choices[$euros . ' €'] = (string) ($euros * 100);
        }
        $choices['Autre montant'] = DonationRequest::OTHER_AMOUNT;

        $builder
            ->add('amount', ChoiceType::class, [
                'label' => 'Montant de votre don',
                'choices' => $choices,
                'expanded' => true,
            ])
            ->add('customAmount', MoneyType::class, [
                'label' => 'Montant libre',
                'required' => false,
                'currency' => 'EUR',
                'divisor' => 100,
                'input' => 'integer',
                'html5' => true,
                'attr' => ['min' => DonationRequest::MIN_AMOUNT / 100, 'step' => 1],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Un petit mot ? (facultatif)',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('consent', CheckboxType::class, [
                'label' => 'J\'accepte que mes données soient utilisées pour traiter mon don et m\'adresser un email de confirmation.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DonationRequest::class,
        ]);
    }
}
