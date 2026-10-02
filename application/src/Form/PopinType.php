<?php

namespace App\Form;

use App\Entity\Image\PopinImage;
use App\Entity\Popin;
use Aropixel\AdminBundle\Form\Type\DateTimeType;
use Aropixel\AdminBundle\Form\Type\EditorType;
use Aropixel\AdminBundle\Form\Type\Image\Single\ImageType;
use Aropixel\AdminBundle\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Popin>
 */
class PopinType extends AbstractType
{
    public const TYPE_CONTENT = 'content';
    public const TYPE_IMAGE = 'image';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Popin|null $popin */
        $popin = $builder->getData();

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de popin',
                'choices' => [
                    'Contenu texte' => self::TYPE_CONTENT,
                    'Image' => self::TYPE_IMAGE,
                ],
                'expanded' => true,
                'mapped' => false,
                'data' => $popin?->getImage() ? self::TYPE_IMAGE : self::TYPE_CONTENT,
            ])
            ->add('content', EditorType::class, [
                'label' => 'Contenu',
                'required' => false,
                'toolbar' => 'simple',
            ])
            ->add('image', ImageType::class, [
                'label' => 'Image',
                'description' => 'JPG ou PNG. Taille recommandée : 800 x 1200 px.',
                'data_class' => PopinImage::class,
                'required' => false,
            ])
            ->add('link', TextType::class, [
                'label' => 'Lien',
                'required' => false,
                'help' => 'Optionnel. Sur une popin image, toute l\'image est cliquable.',
            ])
            ->add('linkLabel', TextType::class, [
                'label' => 'Texte du bouton',
                'required' => false,
                'help' => 'Popin texte uniquement. Par défaut : « En savoir plus ».',
            ])
            ->add('forceDisplay', ToggleSwitchType::class, [
                'label' => 'Forcer l\'affichage à chaque visite (ignorer le cookie)',
                'required' => false,
            ])
            ->add('displayAll', ToggleSwitchType::class, [
                'label' => 'Afficher sur tout le site',
                'required' => false,
            ])
            ->add('urls', TextareaType::class, [
                'label' => 'Pages où afficher la popin',
                'required' => false,
                'help' => 'Une URL par ligne, ex. /spectacles ou https://pebarre.com/nova. Le joker * est accepté : /nova*',
                'attr' => ['rows' => 5],
            ])
            ->add('publishAt', DateTimeType::class, [
                'label' => 'Publier le',
                'required' => false,
            ])
            ->add('publishUntil', DateTimeType::class, [
                'label' => 'Publier jusqu\'au',
                'required' => false,
            ])
            ->add('status', HiddenType::class)
        ;

        $builder->get('urls')->addModelTransformer(new CallbackTransformer(
            static fn (?array $urls): string => implode("\n", $urls ?? []),
            static fn (?string $text): array => array_values(array_filter(array_map('trim', preg_split('/\R/', $text ?? '') ?: []))),
        ));

        // Une popin est soit texte, soit image : on vide le champ du type non retenu.
        $builder->addEventListener(FormEvents::SUBMIT, static function (FormEvent $event): void {
            /** @var Popin $popin */
            $popin = $event->getData();

            if (self::TYPE_IMAGE === $event->getForm()->get('type')->getData()) {
                $popin->setContent(null);
                $popin->setLinkLabel(null);
            } else {
                $popin->setImage(null);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Popin::class,
        ]);
    }
}
