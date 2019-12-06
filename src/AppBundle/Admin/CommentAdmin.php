<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

//use Sonata\Form\Type\DatePickerType;
use Sonata\Form\Type\DateTimePickerType;

final class CommentAdmin extends AbstractAdmin
{

    // make custom sort page
    protected $datagridValues = [
        '_page' => 1, //1st page will display 1st
        '_sort_order' => 'DESC', //newest 1st
        '_sort_by' => 'createdAt', //by created_at date
    ];

    /** @var array $dateFormat  minimal ready format for datetime in sonata */
    protected $dateFormat = [
        'date_format' => 'd/m/Y',
        'format' => 'd/m/Y',
        'model_timezone' => 'Asia/Bishkek',
        'view_timezone' => 'Asia/Bishkek'
    ];

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->tab('General')
                ->with('Comment info')
                ->add('content', TextareaType::class, ['attr' => ['class' => 'ckeditor']])
                ->add('author', null, [ 'label' => 'Author(Name Surname)'])
                ->add('isActive', CheckboxType::class, ['label' => 'label.is_activated', 'required' => false])
                ->add('createdAt', DateTimePickerType::class, ['label' => 'createdAt', 'format' => 'dd/MM/yyyy HH:mm'])
                ->end()
            ->end()
            ->tab('Comment information')
                ->with('Post information')
                ->add('post', null, [ 'label' => 'post (title)'])
                //->add('post.commentsCount', 'integer', [ 'label' => 'Number of comments', 'disabled' => true])
                ->end()
            ->end()
        ;
    }

    /**
     * Search filters
     *
     * @param \Sonata\AdminBundle\Datagrid\DatagridMapper $datagridMapper
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('content')
            ->add('isActive', null, [ 'choices' => ['1' => 'Active', '0' => 'Disabled'] ])
            ->add(
                'createdAt',
                'doctrine_orm_date',
                ['field_type'=>'sonata_type_datetime_picker', 'label' => 'Created at dd/mm/yyyy '],
                null,
                ['format' => 'dd/MM/yyyy', 'widget' => 'single_text']
            )//for more support see  composer require symfony/intl
            ->add('post.author.email', null, ['label' => 'email of author of the post'])
            ->add('author.username', null, ['label' => 'commentator\'s login'])
            ->add('author.name', null, ['label' => 'commentator\'s  name'])
            ->add('author.surname', null, ['label' => 'commentator\'s surname'])
            ->add('author.email', null, ['label' => 'commentator\'s email'])
        ;
    }

    /**
     * List of comments
     * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, [ 'label' => 'ID', 'header_style' => 'width: 5px;'])
            ->addIdentifier('post.title', 'text', ['label' => 'For Post with Title'])
            ->addIdentifier('post.isActive', 'boolean', ['label' => 'label.post_state'])
            ->addIdentifier('previewContent', null, ['label' => 'Comment', 'label_icon' => 'fa fa-i-cursor'])
            ->addIdentifier('isActive', 'boolean', [ 'label' => 'label.comment_state'])
            ->addIdentifier('createdAt', 'datetime', array_merge(['label' => 'Created'], $this->dateFormat))
            ->addIdentifier('author.shortNameS', null, ['label' => 'Author'])
            ->addIdentifier('author.email', null, ['label' => 'AuthorEmail'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();

        $actions['delete'] = [
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Delete'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => true,
        ];

        $actions['publish'] = [
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Publish'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => false,
        ];

        $actions['drafter'] = [
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Draft'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => false,
        ];

        return $actions;
    }
}
