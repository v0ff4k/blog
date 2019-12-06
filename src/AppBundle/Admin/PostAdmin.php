<?php

namespace AppBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

//use Sonata\Form\Type\DatePickerType;
use Sonata\Form\Type\DateTimePickerType;

final class PostAdmin extends AbstractAdmin
{

    /**
     * Default init state for listing
     * @var array $datagridValues
     */
    protected $datagridValues = [

        '_page' => 1, //1st page will display 1st
        '_sort_order' => 'DESC', //newest 1st
        '_sort_by' => 'createdAt', //by updated  date
    ];

    /**
     * Default numbers per page variations.
     * @var array $perPageOptions
     */
    protected $perPageOptions = [10, 20, 40, 100, 300];

    /**
     * Default date format
     * @var array $dateFormat
     */
    protected $dateFormat = [
        'date_format' => 'd/m/Y',
        'format' => 'd/m/Y',
        'model_timezone' => 'Asia/Bishkek',
        'view_timezone' => 'Asia/Bishkek'
    ];

    protected $sonataDateTimeFormat = [
        'format' => 'dd/MM/yyyy HH:mm', //HTML5_FORMAT = "yyyy-MM-dd'T'HH:mm:ss";
    ];

    /**
     * Edit route
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        $id = 'new post';
        if ($this->getRoot()->getSubject() && $this->getRoot()->getSubject()->getId()) {
            $id = 'id: ' . $this->getRoot()->getSubject()->getId();
        }

        $formMapper
            ->tab('General')
                ->with('Main fields for ' . $id)
                    //->add('id')
                    ->add('title', TextType::class, ['label' => 'label.title'])
                    ->add('slug', TextType::class, ['label' => 'slug, (generated on title)', 'required' => false ])//automate UPDATED!!!!
                    ->add('preview', TextareaType::class, [
                        'attr' => ['class' => 'ckeditor'],
                        'label' => 'label.preview'
                        ])
                    ->add('content', TextareaType::class, [
                        'attr' => ['class' => 'ckeditor'],
                        'label' => 'label.content'
                    ])
                    ->add('author', null, ['label' => 'label.author'])
                    ->add('isActive', CheckboxType::class, ['label' => 'label.is_activated', 'required' => false])
                ->end()
            ->end()
            ->tab('SEO')
                ->with('key-description')
                    ->add('keywords', TextType::class, ['label' => 'label.keywords'])
                    ->add('description', TextType::class, ['label' => 'label.description'])
                ->end()
                ->with('Date and time of Post, Created-Updated(auto written/updated)')
                    ->add(
                        'createdAt',
                        DateTimePickerType::class,
                        array_merge(['label' => 'label.created_at'], $this->sonataDateTimeFormat)
                    )
                    ->add(
                        'updatedAt',
                        DateTimePickerType::class,
                        array_merge(['label' => 'label.updated_at'], $this->sonataDateTimeFormat)
                    )
                    ->add('rating', IntegerType::class, [
                        'required' => false,
                        'attr' => \AppBundle\Entity\Post::$ratingsAttr,
                        'label' => 'label.rating'
                    ])
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
            ->add('id', 'doctrine_orm_number')
            ->add('title', 'doctrine_orm_string')
            ->add('slug')
            //->add('preview', 'doctrine_orm_boolean', ['label' => 'With Preview'])
            //->add('content', 'doctrine_orm_boolean', ['label' => 'With Content'])
            ->add('isActive', null, ['label' => 'Post published?'])
            ->add('keywords')
            ->add('description')
            ->add('author.name', null, ['label' => 'Authors name'])
            ->add('author.surname', null, ['label' => 'Authors surname'])
            ->add('author.username', null, ['label' => 'Authors login'])
            ->add('author.email', null, ['label' => 'Authors email'])
            ->add(
                'createdAt',
                'doctrine_orm_date',
                ['field_type'=>'sonata_type_datetime_picker', 'label' => 'Created at dd/mm/yyyy '],
                null,
                ['format' => 'dd/MM/yyyy', 'widget' => 'single_text']
            )//for more support see  composer require symfony/intl
            ->add(
                'updatedAt',
                'doctrine_orm_date',
                ['field_type'=>'sonata_type_datetime_picker', 'label' => 'Updated at dd/mm/yyyy '],
                null,
                ['format' => 'dd/MM/yyyy', 'widget' => 'single_text']
            )//for more support see  composer require symfony/intl
            ->add(
                'rating',
                'doctrine_orm_choice',
                [],
                'choice',
                ['choices' => array_flip(\AppBundle\Entity\Post::$ratings), 'label' => 'Ratingg']
            )
        ;
    }

    /**
     * Post listing table
     *
     * @param \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('id', null, array( 'label' => 'ID', 'header_style' => 'width: 5px;'))
            ->addIdentifier('title')
            ->addIdentifier('slug', 'boolean')
            ->addIdentifier('preview', 'boolean')
            ->addIdentifier('content', 'boolean')
            ->addIdentifier('isActive', 'boolean', [ 'label' => 'label.is_activated' ])
            //->addIdentifier('author.username', TextType::class, [ 'label' => 'Author\'s Login'])
            ->addIdentifier('author.fullName', TextType::class, [ 'label' => 'Author\'s FullName'])
            ->addIdentifier('keywordsCount', null, [ 'label' => 'Keywords' ])
            ->addIdentifier('description', 'boolean', [ 'label' => 'Desc.' ])
            ->addIdentifier(
                'createdAt',
                'datetime',
                array_merge(['label' => 'created'], $this->dateFormat)
            )
            ->addIdentifier(
                'updatedAt',
                'datetime',
                array_merge(['label' => 'updated'], $this->dateFormat)
            )
            ->addIdentifier('getCommentsCount', IntegerType::class, [ 'label' => 'Comments'])
            ->addIdentifier(
                'rating',
                'choice',
                ['choices' => \AppBundle\Entity\Post::$ratings, 'label' => 'Rating']
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBatchActions()
    {
        $actions = parent::getBatchActions();

        $actions['delete'] = array(
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Delete'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => true,
        );

        $actions['publish'] = array(
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Publish'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => false,
        );

        $actions['drafter'] = array(
            'label' => $this->getLabelTranslatorStrategy()->getLabel('Draft'),
            'translation_domain' => $this->getTranslationDomain(),
            'ask_confirmation' => false,
        );

        return $actions;
    }
}
