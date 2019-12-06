<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Comment;
use AppBundle\Entity\Post;
use AppBundle\Events;
use AppBundle\Form\CommentType;
use AppBundle\Form\PostType;
use AppBundle\Helper\PostHelper;
use AppBundle\Helper\UserHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller used to manage blog contents in the public part of the site.
 */
class BlogController extends BaseController
{

    const BLOG_CACHE_TIME_SEC = 86400;//24h = 60 * 60 * 24

    /**
     * Blog index
     *
     * @param $page
     * @param $_format
     * @return mixed
     */
    public function indexAction($page, $_format)
    {
        $postRepo = $this->getDoctrine()->getManager()->getRepository(Post::class);

        //1st query for getting list of post EXACTLY without comments!
        $posts = $postRepo->findLatestPosts($page);
        //2nd query for gettin only #of comments. some post can has a lot of comments!
        $commentsCountArray = $postRepo->getCommentsForPosts($posts);

        $response = $this->render(
            'AppBundle:blog:index.'.$_format.'.twig',
            ['posts' => $posts, 'commentsCount' => $commentsCountArray]
        );

        // @see https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/cache.html
        $response
            ->setSharedMaxAge(self::BLOG_CACHE_TIME_SEC)
            ->setMaxAge(self::BLOG_CACHE_TIME_SEC)
            ->headers
            ->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Showing Post. Whatever it goes by id/slug, or id-slug, if slug/id - outdated, it will redirect to correct url.
     *
     * @param integer $id
     * @param string $slug
     * @return mixed
     * @throws \Exception
     */
    public function showAction($id, $slug)
    {

        $post = PostHelper::getPostsbyIdOrSlug($id, $slug);

        if (empty($post) or !$post instanceof Post) {
            $noPostsString = $this->translate('post.no_posts_found');
            throw $this->createNotFoundException(
                $noPostsString . ' id: ' . $id . '-' . htmlentities($slug)
            );
        }

        $this->isActivePost($post);

        //if it was empty slug, or id or old slug - EXACTLY redirect to CORRECT url!
        if ((empty($slug) || $id === 0) or $slug != $post->getSlug()) {
            $params = [
                'id' => $post->getId(),
                'slug' => $post->getSlug()
            ];

            return $this->redirectToRoute('blog_post', $params);
        }

        $response = $this->render('AppBundle:blog:show.html.twig', ['post' => $post]);

        $pp = (true ? 'public' : 'private');
        $response->setCache(
            [
                'etag' => 'Post'.$post->getId().$post->getUpdatedAt()->getTimestamp(),
                'last_modified' => $post->getUpdatedAt(),
                'max_age' => self::BLOG_CACHE_TIME_SEC,
                's_maxage' => self::BLOG_CACHE_TIME_SEC,
                $pp => true,
            ]
        );

        // (optional) set a custom Cache-Control directive
        $response->headers->addCacheControlDirective('must-revalidate', true);

        return $response;
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @param Request $request
     * @param Post $post
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, Post $post)
    {
        $this->isActivePost($post);
        $this->denyAccessUnlessGranted(
            'edit',
            $post,
            $this->translate('notification.allowed_only_for_author')
        );
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addTransFlash('success', 'post.updated_successfully');

            return $this->redirectToRoute('blog_post_id', ['id' => $post->getId()]);
        }

        return $this->render(
            'AppBundle:blog:edit.html.twig',
            [
                'post' => $post,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Create new post a form to edit an existing Post entity.
     *
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted(
            'IS_AUTHENTICATED_FULLY',
            null,
            $this->translate('http_error_403.description')
        );
        $post = new Post();
        $post->setAuthor($this->getUser());
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            UserHelper::getInstance()->persistAndFlush($post);
            $this->addTransFlash('success', 'post.created_successfully');

            return $this->redirectToRoute('blog_post_id', ['id' => $post->getId()]);
        }

        return $this->render(
            'AppBundle:blog:create.html.twig',
            [
                'post' => $post,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * "Deletes" a Post|Comment, set it to inactive.
     *
     * The Security annotation value is an expression (if it evaluates to false,
     * the authorization mechanism will prevent the user accessing this resource).
     *
     * @param  string $objectString - post|comment
     * @param integer $id     - number of record
     * @param string $token   - user's token marked as delete
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function deleteAction($objectString, $id, $token)
    {
        if (!$this->isCsrfTokenValid('delete', $token)) {
            return $this->redirectToRoute('blog_index');
        }

        /** @var Comment|Post $object */
        $class = ($objectString == 'comment' ? Comment::class : Post::class);
        $object = $this->getDoctrine()->getRepository($class)->findOneBy(['id' => $id]);

        if ($objectString == 'comment') {
            $message = 'comment.deleted_successfully';
            $route = 'blog_post';
            $p = $object->getPost();
            $params = ['id' => $p->getId(), 'slug' => $p->getSlug()];
        } else {
            $message = 'post.deleted_successfully';
            $route = 'blog_index';
            $params = [];
        }

        if (!$this->isGranted('delete', $object)) {
            $this->addTransFlash('error', 'notification.allowed_only_for_author');

            return $this->redirectToRoute($route, $params);
        }

        $object->deactivate();
        UserHelper::getInstance()->persistAndFlush($object);
        $this->addTransFlash('success', $message);

        return $this->redirectToRoute($route, $params);
    }

    /**
     * Create new comment for some Post.
     *
     * @param Request $request
     * @param Post $post
     * @param EventDispatcherInterface $eventDispatcher
     * @return RedirectResponse|Response
     * @throws \Exception
     */
    public function commentNewAction(Request $request, Post $post, EventDispatcherInterface $eventDispatcher)
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            $this->addTransFlash('success', 'http_error_403.description');

            return $this->redirect($this->generateUrl('security_login'), 301);
        }

        $comment = new Comment();
        $comment->setAuthor($this->getUser());
        $post->addComment($comment);

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            UserHelper::getInstance()->persistAndFlush($comment);

            $this->addTransFlash('success', 'comment.created_successfully');

            //trigger for creating comment
            $event = new GenericEvent($comment);
            $eventDispatcher->dispatch(Events::COMMENT_CREATED, $event);

            $params = [
                'id' => $post->getId(),
                'slug' => $post->getSlug()
            ];

            return $this->redirectToRoute('blog_post', $params);
        }

        return $this->render(
            'AppBundle:blog:comment_form_error.html.twig',
            [
                'post' => $post,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Creating form, directly from template AppBundle:blog:show.html.twig via render()
     *
     * @param Post $post
     * @return Response
     */
    public function commentFormAction(Post $post)
    {
        $form = $this->createForm(CommentType::class);

        return $this->render(
            'AppBundle:blog:_comment_form.html.twig',
            [
                'post' => $post,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * Simple search action.
     *
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function searchAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->render('AppBundle:blog:search.html.twig');
        }

        $token = $request->query->get('_csrf_token', '');
        $query = $request->query->get('q', '');

        if (!empty($query) && !$this->isCsrfTokenValid('search', $token)) {
            return $this->json(
                [
                    'title' => UserHelper::getTrans()->trans('post.no_posts_found'),
                    'preview' => '--//--',
                    'date' => '5/5/5',
                    'url' => $this->generateUrl('blog_index')
                ]
            );
        }

        /** @var Post[] $posts */
        $posts = $this->getDoctrine()->getRepository(Post::class)->findBySearchQuery($query);

        $results = [];
        foreach ($posts as $post) {
            $results[] = [
                'title' => htmlspecialchars($post->getTitle()),
                'date' => $post->getCreatedAt()->format('m/d/Y'),
                'preview' => htmlspecialchars($post->getPreview()),
                'url' => $this->generateUrl('blog_post', ['slug' => $post->getSlug(), 'id' => $post->getId()]),
            ];
        }

        return $this->json($results);
    }
}
