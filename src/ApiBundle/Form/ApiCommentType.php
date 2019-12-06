<?php

namespace ApiBundle\Form;

use AppBundle\Entity\Comment;
use Symfony\Component\OptionsResolver\OptionsResolver;


class ApiCommentType extends \AppBundle\Form\CommentType
{

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            'csrf_protection' => false,
        ]);
    }

}