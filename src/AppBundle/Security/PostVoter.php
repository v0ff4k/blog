<?php

namespace AppBundle\Security;

use AppBundle\Entity\Post;
use AppBundle\Entity\Comment;
use AppBundle\Entity\User;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * It grants or denies permissions for actions related to blog posts (such as showing, editing and deleting posts).
 */
class PostVoter extends Voter
{
    const SHOW = 'show';
    const EDIT = 'edit';
    const CREATE = 'create';
    const DELETE = 'delete';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    public function __construct(AccessDecisionManagerInterface $decisionManager)
    {
        $this->decisionManager = $decisionManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        // from twig   is_granted('action', 'object')
        return (
            ($subject instanceof Post or $subject instanceof Comment) &&
            in_array($attribute, [self::SHOW, self::EDIT, self::CREATE, self::DELETE], true)
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $object, TokenInterface $token)
    {
        // from controller, isGranted()
        /** @var Post|Comment $object */

        $user = $token->getUser();

        if (!$user instanceof User) {//or UserInterface
            return false;
        }
        //return $user === $post->getAuthor();

        switch ($attribute) {
            case self::EDIT:
            case self::DELETE:
                // if user==author + IsActive() + object isActive too !!!
                if ($object->isAuthor($user) && !empty($user->getIsActive()) && !empty($object->getIsActive())) {
                    return true;
                }

                break;
            case self::CREATE:
                if (!empty($user->getIsActive())) {
                    return true;
                }
                break;
        }

        return false;
    }
}
