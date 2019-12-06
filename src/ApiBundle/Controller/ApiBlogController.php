<?php

namespace ApiBundle\Controller;

use ApiBundle\Service\ApiService;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Post;
use AppBundle\Helper\PostHelper;
use AppBundle\Helper\UserHelper;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;

/**
 * Class ApiBlogController
 *
 * @package ApiBundle\Controller
 * @see vendor/symfony/symfony/src/Symfony/Component/HttpFoundation/Response.php  with HTTP_*** statuses
 */
class ApiBlogController extends ApiBaseController
{

    /**
     * Index of blog posts, separated by page
     *
     * @ApiDoc(
     *     resource = true,
     *     description = "Get a list of posts as preview",
     *     output = "AppBundle\Entity\Post",
     *     parameters={
     *      {"name"="page", "dataType"="integer", "required"=true, "description"="page num"}
     *     },
     *     output={"collection"=true, "collectionName"="classes", "class"="AppBundle\Entity\Post"},
     *     statusCodes = {
     *      200 = "Returned when successful",
     *      404 = "Returned when the page is not found"
     *     }
     * )
     * @param integer $page
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction($page)
    {

        /** @var \Pagerfanta\Pagerfanta $posts */
        $posts = $this->getDoctrine()->getManager()->getRepository(Post::class)->findLatestPosts($page);
        if (null === $posts or empty($posts) or !$posts) {
            throw $this->createNotFoundException();
        }

        $route = 'api_index';
        $routeParams = ['page' => $page];
        $pageCollections = ApiService::getPagerfantaCollection($posts, $route, $routeParams, $page);

        return $this->returnApiResponse($pageCollections, null, [], 'json', ['Default', 'list']);
    }


    /**
     * Display one post with some comments
     *
     * @ApiDoc(
     *     resource = true,
     *     description="Returns a Post object with last 10 comments, more comments(paginated) in next method",
     *     requirements={
     *      {
     *          "name"="id",
     *          "dataType"="integer",
     *          "requirement"="\d+",
     *          "description"="id of the Post, if  0 - search by slug"
     *      },
     *      {
     *          "name"="slug",
     *          "dataType"="string",
     *          "requirement"="[a-z0-9\-_]+",
     *          "description"="slug of the Post, if '' - searching by id"
     *      }
     *     },
     *     statusCodes={
     *     200="Returned when successful",
     *     404={
     *          "Returned when the Post is not found",
     *          "Returned when something else is not found"
     *          }
     *     }
     * )
     * @param $id
     * @param $slug
     * @return \Symfony\Component\HttpFoundation\Response|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function showAction($id, $slug)
    {

        $post = PostHelper::getPostsbyIdOrSlug($id, $slug);

        if (!$post->getIsActive()) {
            $this->returnApiResponse(UserHelper::getTrans()->trans('post.inactive'));
        }

        //if it was empty slug, or id or old slug - redirect to correct url!
        if ((empty($slug) || $id === 0) or $slug != $post->getSlug()) {
            $params = [
                'id' => $post->getId(),
                'slug' => $post->getSlug()
            ];

            return $this->redirectToRoute('api_post', $params);
        }

        return $this->returnApiResponse($post, null, [], 'json', ['Default', 'details']);
    }


    /**
     * Get paginated comments for some post.
     *
     * @param integer $postId
     * @param integer $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function indexPostCommentsAction($postId, $page)
    {
        //@todo moveout this fat !
        /** @var \Pagerfanta\Pagerfanta $comments */
        $comments = UserHelper::getDoctrine()->getManager()
            ->getRepository(Comment::class)
            ->findLatestComments($postId, $page);
        if (!$comments) {
            throw $this->createNotFoundException();
        }

        $route = 'api_post_comments_paginated';
        $routeParams = ['postId' => $postId, 'page' => $page];
        $pageCollections = ApiService::getPagerfantaCollection($comments, $route, $routeParams, $page);

        return $this->returnApiResponse($pageCollections, null, [], 'json', ['Default', 'list']);
    }
}
