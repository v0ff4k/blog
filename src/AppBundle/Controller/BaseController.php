<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Post;
use AppBundle\Helper\UserHelper;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class BaseController extends Controller
{

    /**
     * Make translatable flash message.
     *
     * @param $type
     * @param string $message
     * @param array $params
     * @param string $domain
     * @return void
     */
    protected function addTransFlash($type, $message, $params = [], $domain = 'messages')
    {

        $message = UserHelper::getTrans()->trans($message, $params, $domain);
        //$message = $this->get('translator')->trans($message);
        $this->addFlash($type, $message);

        return;
    }

    /**
     * Checker if Post in active state(in trash-"0", or published-"1")
     * @param \AppBundle\Entity\Post $post
     */
    protected function isActivePost(Post $post)
    {
        if (!$post->getIsActive()) {
            $postInactiveText = UserHelper::getTrans()->trans('post.inactive');
            throw $this->createNotFoundException($postInactiveText);
        }
    }


    /**
     * Make translation, lightweight micro method for blog.
     *
     * @param $message
     * @return string
     */
    protected function translate($message, $params = [], $domain = 'messages')
    {
        return UserHelper::getTrans()->trans($message, $params, $domain);
        //return $this->get('translator')->trans($message, $params, $domain);
    }
}
