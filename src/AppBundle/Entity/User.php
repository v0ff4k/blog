<?php

namespace AppBundle\Entity;

use AppBundle\Helper\UserHelper;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Accessor;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * User entity for authors of the blog.
 *      Items marked as  "at"+"Exclude" - wouldn't be shown,
 *      others will displays as usual in API!
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *     name="user",
 *     indexes={
 *          @ORM\Index(name="username_idx", columns={"username"}),
 *          @ORM\Index(name="email_idx", columns={"email"})
 *      },
 * )
 * @ExclusionPolicy("none")
 * @UniqueEntity(fields="username", message="user.existing_username")
 * @UniqueEntity(fields="email", message="user.existing_email")
 * @todo see  what UserInterface
 */
class User extends BaseEntity implements UserInterface, \Serializable
{
    const DEFAULT_ROLE = 'ROLE_USER';

    /**
     * @var string
     *
     * @ORM\Column(type="string",
     * options={"comment" = "Users real name: John, Vasya, Ahmed"})
     * @Assert\Length(
     *     min=3,
     *     max=64
     * )
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string",
     * options={"comment" = "Users real surname: Doe, Pupkin, AlmuhandiIbn"})
     * @Assert\Length(
     *     min=3,
     *     max=128
     * )
     */
    private $surname;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true,
     * options={"comment" = "Users original logins: john_doe, vasya80, shatatel "})
     * @Assert\Length(
     *     min=0,
     *     minMessage="user.blank_username",
     *     max=128
     * )
     * @Exclude
     */
    private $username;

    /**
     * Virtual var for temp storing plain passwords
     * if it sets - it will update password
     *
     * @var string
     * @Assert\Length(max=128)
     * @Exclude
     */
    private $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true,
     * options={"comment" = "Users original emails: jhon.doe@serv.com, vasya80@mail.ru, ah@smail.ir"})
     * @Assert\Length(min=0, minMessage="user.blank_email, max=128")
     * @Assert\Email(message="user.wrong_email")
     * @Accessor(getter="getObfusicatedEmail")
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Exclude
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="token", type="string", unique=true, length=32)
     * @Exclude
     */
    private $token;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array")
     * @Exclude
     */
    private $roles = [ self::DEFAULT_ROLE ];


//    public function __construct()
//    {
//        $this->roles = [ self::DEFAULT_ROLE ];
//        (!$this->token) ?: $this->generateToken();
//    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = UserHelper::sanitizeVal($name, true);

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $surname
     * @return self
     */
    public function setSurname($surname)
    {
        $this->surname = UserHelper::sanitizeVal($surname, true);

        return $this;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * @param string $username
     * @return self
     */
    public function setUsername($username)
    {
        $this->username = UserHelper::sanitizeVal($username, true);

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getRealUsername()
    {
        return $this->username;
    }

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;
        $this->password = null;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getObfusicatedEmail()
    {
        return UserHelper::hideEmail($this->email);
    }

    /**
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getSalt()
    {
        // All passwords must be hashed with a salt,
        // but bcrypt and argon2i do this internally !!!!!
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return self
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set token
     *
     * @param string|null $token
     * @return self
     */
    public function setToken($token = null)
    {
        if ($token) {
            $this->token = $token;
        } else {
            $this->generateToken();
        }

        return $this;
    }

    /**
     * Generate new token string for current user and return value
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     * @return self
     */
    public function generateToken()
    {
        $token = md5(date('Y-m-d H:i:s u') . rand(9, 999));
//        $this->setToken(md5(date('c') . rand(300, 900)));
        return $this->setToken($token);
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get stringRoles
     *
     * @return string
     */
    public function getStringRoles()
    {
        return $this->roles ? implode(',', $this->roles) : 'ROLE_USER';
    }

    public function setStringRoles($role)
    {
        $this->roles = explode(',', $role);
        return $this;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles()
    {
        $roles = $this->roles;

        if (empty($roles)) {
            $roles[] = self::DEFAULT_ROLE;
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles)
    {

        $this->roles = (empty($roles) ? "[{self::DEFAULT_ROLE}]": $roles);

        return $this;
    }

    public static function getListRoles()
    {
        return [
            'ROLE_SUPER_ADMIN' => 'superadmin',
            'ROLE_ADMIN' => 'admin',
            'ROLE_USER' => 'user',
        ];
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
         $this->plainPassword = null;
    }

    /**
     * Magic method for displaying when calls echo(User) or print(User)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFullName();
    }

    public function getFullName()
    {
        return '' . $this->name . ' ' . $this->surname;
    }

    public function getShortNameS()
    {
        return '' . $this->name . ' ' . ucwords($this->surname[0]) . '. ';
    }

    /**
     * @see \Serializable::serialize()
     * @return string
     */
    public function serialize()
    {
        return serialize([
            $this->id,
            $this->username,
            $this->password,
            $this->email
        ]);
    }

    /**
     * @see \Serializable::unserialize()
     * @param string $serialized
     * @return void|mixed
     */
    public function unserialize($serialized)
    {
        list(
            $this->id,
            $this->username,
            $this->password,
            $this->email
        ) = unserialize($serialized);
    }
}
