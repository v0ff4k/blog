<?php

namespace AppBundle\Entity;

use AppBundle\Helper\UserHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Groups;
use JMS\Serializer\Annotation\MaxDepth;
use JMS\Serializer\Annotation\Type;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @TODO add  _idx !!!!!!!
 * Post entity - main content of the blog.
 *          Items marked as  "at"+"Exclude" - wouldn't be shown,
 *          others will displays as usual in API!
 *
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PostRepository")
 * @ORM\Table(
 *     name="post",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(name="slug_idx", columns={"slug"})
 *          }
 *     )
 * @ExclusionPolicy("none")
 */
class Post extends BaseEntity
{

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    private $title;

    /**
     * @var string
     *
     * @Gedmo\Slug(fields={"title"})
     * @ORM\Column(name="slug", type="string", length=128, nullable=false, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=250,
     *     options={"comment"="Short preview with HTML"}))
     * @Assert\NotBlank(message="post.blank_preview")
     * @Assert\Length(
     *     min=100, minMessage="post.too_short_preview",
     *     max=250, maxMessage="post.too_long_preview"
     * )
     * @Groups({"list"})
     */
    private $preview;

    /**
     * @var string
     *
     * @ORM\Column(type="text", length=65535,
     *     options={"comment"="HTML content of the blog"}))
     * @Assert\NotBlank(message="post.blank_content")
     * @Assert\Length(
     *     min=100, minMessage="post.too_short_content",
     *     max=65535, maxMessage="post.too_long_content"
     * )
     * @Groups({"details"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Exclude
     */
    private $keywords = 'blog, test, code, example';

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Exclude
     */
    private $description = 'Description for current post.';

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Range(
     *     min=0, minMessage="post.too_short_rate",
     *     max=5, maxMessage="post.too_long_rate"
     * )
     * @Groups({"details"})
     */
    private $rating = 0;

    /**
     * Star rating, used mostly in /admin/ part
     *
     * @Groups({"info"})
     * @var array $ratings
     */
    public static $ratings = [
        null => 'not set',
        0 => '☆☆☆☆☆',
        1 => '★☆☆☆☆',
        2 => '★★☆☆☆',
        3 => '★★★☆☆',
        4 => '★★★★☆',
        5 => '★★★★★',
    ];

    /**
     * Rating attributes for edit route
     *
     * @Groups({"info"})
     * @var array $ratingsAttr
     */
    public static $ratingsAttr = [
        'min' => 0,
        'max' => 5,
        'placeholder' => '0 - 5'
    ];

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", name="updated_at")
     * @Assert\DateTime
     * @Type("DateTime<'d-m-Y'>")
     */
    private $updatedAt;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     * @MaxDepth(2)
     */
    private $author;

    /**
     * @var Comment[]|ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="post",
     *      orphanRemoval=true,
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\OrderBy({"createdAt": "DESC"})
     * @ORM\Cache()
     * @MaxDepth(2)
     * @Groups({"details"})
     */
    private $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = UserHelper::sanitizeVal($title, true);
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = UserHelper::sanitizeVal($slug, true);
    }

    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = UserHelper::sanitizeVal($content);
    }

    /**
     * Get Keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    public function getKeywordsCount()
    {
        return count(explode(',', $this->keywords));
    }

    /**
     * Set Keywords
     *
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->keywords = UserHelper::sanitizeVal($keywords, true);
    }

    /**
     * Get Description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }


    public function getDescriptionCount()
    {
        return count(explode(',', $this->description));
    }

    public function getDescriptionWordCount()
    {
        return count(explode(' ', $this->description));
    }

    /**
     * Set Description
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = UserHelper::sanitizeVal($description, true);
    }

    /**
     * Get Rating
     *
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set Rating
     *
     * @param int $rating
     */
    public function setRating($rating)
    {
        $this->rating = $rating;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
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

    public function getComments()
    {
        $maxResults = UserHelper::getNumPerPage();
        $params = ['createdAt' => \Doctrine\Common\Collections\Criteria::DESC];
        $criteria = \Doctrine\Common\Collections\Criteria::create()
            ->orderBy($params)
            ->setMaxResults($maxResults);

        return $this->comments->matching($criteria);
    }

    public function getCommentsAll()
    {
        return $this->comments;
    }

    public function getCommentsCount()
    {
        return $this->comments->count();
    }

    public function addComment(Comment $comment)
    {
        $comment->setPost($this);
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }
    }

    public function removeComment(Comment $comment)
    {
        $comment->setPost(null);
        $this->comments->removeElement($comment);
    }

    public function getPreview()
    {
        return $this->preview;
    }

    /**
     * @param string $preview
     */
    public function setPreview($preview)
    {
        $this->preview = $preview;
    }

    /**
     * Magic method for displaying when calls echo(User) or print(User)
     *
     * @return string
     */
    public function __toString()
    {
        return '' . $this->title;
    }
}
