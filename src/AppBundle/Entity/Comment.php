<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Helper\UserHelper;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Comment entity for post comments of the blog.
 *          Items marked as  "at"+"Exclude" - wouldn't be shown,
 *          others will displays as usual in API!
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CommentRepository")
 * @ORM\Table(name="comment")
 * @ExclusionPolicy("none")
 */
class Comment extends BaseEntity
{

    /**
     * @var Post
     *
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(2)
     * @Exclude
     */
    private $post;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="comment.blank")
     * @Assert\Length(
     *     min=5,
     *     minMessage="comment.too_short",
     *     max=5000,
     *     maxMessage="comment.too_long"
     * )
     */
    private $content;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(2)
     */
    private $author;

    public function __construct()
    {
        //do some on create this entity
    }

    /**
     * @Assert\IsTrue(message="comment.is_spam")
     */
    public function isLegitComment()
    {
        //$containsInvalidCharacters = false !== mb_strpos($this->content, '@');
        //return !$containsInvalidCharacters;

        $needle = ['@', 'http://', 'www', 'viagra', 'cialis'];//etc
        foreach ($needle as $query) {
            if (mb_strpos($this->content, $query) !== false) { // stop on first
                return false;
            }
        }
        return true;
    }

    public function getPost()
    {
        return $this->post;
    }

    public function setPost(Post $post)
    {
        $this->post = $post;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function getPreviewContent($maxWords = 5)
    {
        return $this->getFirstWords($this->content, $maxWords);
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = UserHelper::sanitizeVal($content, 'safe');
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;
    }

    /**
     * Checking isAuthor via TWIG
     *
     * @param \AppBundle\Entity\User|null $user
     * @return bool
     */
    public function isAuthor(User $user = null)
    {
        return $user && $user->getEmail() === $this->getAuthor()->getEmail();
    }


    /**
     * Magic method for displaying first 5 words from content,
     *      when calls echo(User) or print(User)
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getPreviewContent(5);
    }
}
