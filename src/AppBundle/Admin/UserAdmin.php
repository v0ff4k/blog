<?php

namespace AppBundle\Admin;

use AppBundle\Entity\User;

use AppBundle\Helper\UserHelper;
//use Doctrine\DBAL\Types\ArrayType;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

//use Sonata\AdminBundle\Form\Type\Filter\ChoiceType;
//use Sonata\AdminBundle\Form\Type\ModelType;
//use Sonata\DoctrineORMAdminBundle\Filter\CallbackFilter;
//use Symfony\Component\Form\Extension\Core\Type\TextType;
//use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
//use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * Class UserAdmin
 *
 * some manuals for  ->add(name, type, options)
 * @see name - name or method in your Entity
 * @see type - https://symfony.com/doc/3.x/bundles/SonataAdminBundle/reference/action_list.html#available-types-and-associated-options
 * @see options - https://symfony.com/doc/3.x/bundles/SonataAdminBundle/reference/action_list.html#options
 * @package AppBundle\Admin
 */
final class UserAdmin extends AbstractAdmin
{

    /**
     * Default numbers per page variations.
     * @var array $perPageOptions
     */
    protected $perPageOptions = [10, 20, 40, 100, 300];

    /**
     * Prepare before persist
     * {@inheritdoc}
     * @param $object
     */
    public function prePersist($object)
    {
        parent::prePersist($object);
        $this->updateUser($object);
    }

    /**
     * Prepare before update.
     * {@inheritdoc}
     * @param $object
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);
        $this->updateUser($object);
    }

    /**
     * Update User, before persist and update.
     * @param \AppBundle\Entity\User $u
     */
    public function updateUser(User $u)
    {
        //some real big funct goes here when user is updated...
        UserHelper::getLogg()->info('updating user:' . json_encode($u));
    }

    /**
     * Called on /create/  in our case, it is a User object.
     * @return \AppBundle\Entity\User|mixed
     */
    public function getNewInstance()
    {
        /** @var User $instance */
        $instance = parent::getNewInstance();
        //set generated token string as default value
        $instance->generateToken();

        return $instance;
    }

    /**
     * Micro ORM injection for support user roles
     *
     * @param \Doctrine\ORM\QueryBuilder  $queryBuilder
     * @param $alias
     * @param $field
     * @param $value
     * @return mixed|void|bool
     */
    private function getStringRolesQuery($queryBuilder, $alias, $field, $value)
    {
        if (!$value['value']) {
            return;
        }
        $val = '%' . $value['value'] . '%';
        $queryBuilder->andWhere($alias.'.roles LIKE :role')->setParameter('role', $val);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {

        $passOptions = [
            'type' => 'password',
            'label' => 'admin.new_password_fill',
            //'first_options' => ['label' => 'admin.password_fill'],
            'second_options' => ['label' => 'label.password_repeat'],
//           'required' => false,
//           'translation_domain' => 'messages',
            'invalid_message' => 'label.password.mismatch',
        ];

        $recordId = $this->request->get($this->getIdParameter());
        $passOptions['required'] = (!empty($recordId)) ? false : true;
        $passOptions['first_options']['label'] = (!empty($recordId))
            ? 'admin.password_fill' // "live empty - means no changes"
            : 'label.password'; // strictly must be filled!

        $formMapper
            ->add('name', 'text', ['label' => 'label.name'])
            ->add('surname', 'text', ['label' => 'label.surname'])
            ->add('username', 'text', ['label' => 'label.login'])
            ->add('email', 'text', ['label' => 'label.email'])
            ->add('isActive', 'checkbox', ['label' => 'admin.user_is_active_q'])
            ->add('plainPassword', 'repeated', $passOptions)
            ->add('token', 'text', ['label' => 'admin.user_s_token'])
            ->add('stringRoles', 'choice', [
                'choices' => array_flip(User::getListRoles()),
                'label' => 'admin.select_role'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id', null, ['label' => 'label.id'])
            ->add('name', null, ['label' => 'label.name'])
            ->add('surname', null, ['label' => 'label.surname'])
            ->add('username', null, ['label' => 'label.login'])
            ->add('email', null, ['label' => 'label.email'])
            ->add('isActive', null, ['label' => 'label.is_activated'])
            ->add('token', null, ['label' => 'admin.token'])
//            ->add('roles', 'doctrine_orm_choice', [], 'choice', [
//                'choices' => $this->roles, 'label' => 'Role'
//            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureListFields(ListMapper $listMapper)
    {

        $listMapper
            ->addIdentifier('id', null, ['label' => 'label.id'])
//            ->addIdentifier('name', null, ['label' => 'label.name'])
//            ->addIdentifier('surname', null, ['label' => 'label.surname'])
            ->addIdentifier('shortNameS', null, ['label' => 'admin.fullname'])
            ->addIdentifier('username', null, ['label' => 'label.login'])
            ->addIdentifier('email', null, ['label' => 'label.email'])
            ->addIdentifier('isActive', null, ['label' => 'label.is_activated'])
            //->addIdentifier('password', null, ['label' => 'New pass'])
            //->addIdentifier('token', null, ['label' => 'User\'s token'])
            ->addIdentifier('stringRoles', 'choice', [
                'choices' => User::getListRoles(),
                'label' => 'label.role'])
        ;
    }
}
