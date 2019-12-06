<?php

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Defines the sample users to load in the database before running the unit and
 * functional tests. Execute this command to load the data.
 *
 *   $ php bin/console doctrine:fixtures:load
 * -----------------------------------------------------
 * @package AppBundle\DataFixtures\ORM
 */
class UserFixtures extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $vasyaAdmin = new User();
        $vasyaAdmin->setName('Vasya');
        $vasyaAdmin->setSurname('Pupkin');
        $vasyaAdmin->setUsername('vsya_pupkin');
        $vasyaAdmin->setEmail('vsya_pupkin@serv.com');
        $vasyaAdmin->setRoles(['ROLE_SUPER_ADMIN']);
        $vasyaAdmin->setToken(md5(date('r') . rand(0, 999)));
        $encodedPassword = $passwordEncoder->encodePassword($vasyaAdmin, 'imr3aLman');
        $vasyaAdmin->setPassword($encodedPassword);
        $manager->persist($vasyaAdmin);
        $this->addReference('vasya', $vasyaAdmin);

        $kateAdm = new User();
        $kateAdm->setName('Kate');
        $kateAdm->setSurname('Ivanova');
        $kateAdm->setUsername('kate_i');
        $kateAdm->setEmail('kate_i@serv.com');
        $kateAdm->setRoles(['ROLE_ADMIN']);
        $kateAdm->setToken(md5(date('r') . rand(0, 999)));
        $encodedPassword = $passwordEncoder->encodePassword($kateAdm, 'kait-ttt-ty');
        $kateAdm->setPassword($encodedPassword);
        $manager->persist($kateAdm);
        $this->addReference('kate-admin', $kateAdm);

        $johnUser = new User();
        $johnUser->setName('John');
        $johnUser->setSurname('Doe');
        $johnUser->setUsername('john_user');
        $johnUser->setEmail('john_user@serv.com');
        $johnUser->setToken(md5(date('r') . rand(0, 999)));
        $encodedPassword = $passwordEncoder->encodePassword($johnUser, 'kitten');
        $johnUser->setPassword($encodedPassword);
        $manager->persist($johnUser);
        $this->addReference('john-user', $johnUser);

        $manager->flush();
    }
}
