<?php

namespace AppBundle\Helper;

use AppBundle\Entity\Post;

class PostHelper
{


    /**
     * Removes all non-alphanumeric characters except whitespaces.
     *
     * @param string $query
     * @return string
     */
    public static function sanitizeString($query)
    {
        $noSpace = trim(preg_replace('/[[:space:]]+/', ' ', $query));
        return preg_replace('/[^[:alnum:] ]/', '', $noSpace);
    }

    /**
     * Splits the search query into terms and removes the ones which are irrelevant.
     *
     * @todo make more beautify, or make less ugly return.
     * @param string $searchQuery
     * @return array
     */
    public static function extractSearchTerms($searchQuery)
    {
        $terms = array_unique(explode(' ', mb_strtolower($searchQuery)));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }


    /**
     * Getter posts by id and or slug, or return newest active post
     *
     * @param $id
     * @param $slug
     * @return \AppBundle\Entity\Post|null|object
     * @throws \Exception
     */
    public static function getPostsbyIdOrSlug($id = 0, $slug = '')
    {
        $em = UserHelper::getDoctrine()->getManager();
        $postRepo = $em->getRepository(Post::class);

        if (!empty($post = $postRepo->findPostByIdAndSlug($id, $slug))) {
            //post can be founded normally
            return $post;
        } else {// just get latest post
            UserHelper::getLogg()->error('Not set id+slug, receiving new active post');

            return $postRepo->findOneBy(['isActive' => '1', 'id' => 'DESC']);
        }

        /// old
//        /** @var Post $post */
//        if ($id > 0 && empty($slug)) {
//            $post = $postRepo->findOneBy(['id' => $id, 'isActive' => '1']);
//        } elseif (!empty($slug) && $id === 0) {
//            $post = $postRepo->findOneBy(['slug' => $slug, 'isActive' => '1']);
//        } elseif ($id > 0 and !empty($slug)) {
//            $post = $postRepo->findPostByIdAndSlug($id, $slug);
//        } else {// just get latest post
//            self::getLogg()->error('Not set id+slug, receiving new active post');
//            $post = $postRepo->findOneBy(['isActive' => '1'], ['id' => 'DESC']);
//        }
//
//        return $post;
        ///old end
    }


}