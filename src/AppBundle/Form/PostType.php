<?php

namespace AppBundle\Form;

use AppBundle\Entity\Post;
//use AppBundle\Form\Type\DateTimePickerType;
use blackknight467\StarRatingBundle\Form\RatingType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * blog's Post form
 */
class PostType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, [
                'attr' => ['autofocus' => true],
                'label' => 'label.title',
            ])
            ->add('keywords', null, [
                'label' => 'label.keywords',
            ])
            ->add('description', null, [
                'label' => 'label.description',
            ])
            ->add('rating', RatingType::class, [
                'label' => 'label.rating'
            ])
            ->add('preview', TextareaType::class, [
                'label' => 'label.preview',
            ])
            ->add('content', null, [
                'attr' => ['rows' => 20, 'class' => 'tinymce'],
                'label' => 'label.content',
            ])
//            ->add('createdAt', DateTimePickerType::class, [
//                'label' => 'label.created_at',
//            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Post::class,
        ]);
    }
}
