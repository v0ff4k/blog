<?php

namespace AppBundle\Security;

use AppBundle\Helper\UserHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Class AccessDeniedHandler
 * simple handle access denied as 403 response.
 * @package AppBundle\Security
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{

    public function handle(Request $request, AccessDeniedException $accessDeniedException)
    {
        // ...
        $content = UserHelper::getTrans()->trans('http_error_403.description');
        return new Response($content, Response::HTTP_FORBIDDEN);
    }
}
