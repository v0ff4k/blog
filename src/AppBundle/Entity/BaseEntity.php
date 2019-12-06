<?php

namespace AppBundle\Entity;

use AppBundle\Helper\UserHelper;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\MappedSuperclass;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class BaseEntity
 *
 * Base entity class to be extended by others entities with same fields, using KISS+DRY
 * @MappedSuperclass
 * @ExclusionPolicy("none")
 * @package AppBundle\Entity
 */
class BaseEntity
{

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(name="id", type="integer")
     */
    protected $id;

    /**
     * @var \DateTime
     *
     * @Assert\DateTime
     * @ORM\Column(name="created_at", type="datetime",
     *     options={"comment"="creation time"})
     * @Gedmo\Timestampable(on="create")
     * @Type("DateTime<'d-m-Y'>")
     */
    protected $createdAt;


    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean",
     *     options={"default" = 1, "comment"="Activated (1 - yes, 0 - nope)"}))
     * @Exclude()
     */
    protected $isActive = true;

    /**
     * Get id
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt = null)
    {
        //setting default value, now, when empty or not \DateTime
        $createdAt = (empty($createdAt) or !$createdAt instanceof \DateTime) ?  new \DateTime("now") : $createdAt;

        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Set inactive, isActive = false
     *
     * @return $this
     */
    public function deactivate()
    {
        $this->isActive = false;
        return $this;
    }

    /**
     * Set activated, isActive = true
     * @return $this
     */
    public function activate()
    {
        $this->isActive = true;
        return $this;
    }

    /**
     * Gets first n-words without html tags
     *
     * @param $string
     * @param int $maxWords
     * @return string
     */
    public function getFirstWords($string, $maxWords = 5)
    {
        $string = UserHelper::sanitizeVal($string, true);
        $words = explode(' ', $string);
        $sliced = array_slice($words, 0, $maxWords);
        $dots = (count($words) > count($sliced)) ? '...' : '';

        return '' . implode(' ', $sliced) . $dots;
    }
}
