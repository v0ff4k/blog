<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class RedirectingController
 *      micro controller for redirects to correct translation
 * @package AppBundle\Controller
 */
class RedirectingController extends BaseController
{

    /**
     * Redirecting controller to redirect all what not founded by norm routes WITHOUT ending slash sign "/"
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function trailingSlashRedirectAction(Request $request)
    {
        $pathInfo = $request->getPathInfo();
        $requestUri = $request->getRequestUri();
        $url = str_replace($pathInfo, rtrim($pathInfo, ' /'), $requestUri);

        // 308 (Permanent Redirect) is similar to 301 (Moved Permanently) except
        // that it DOES NOT allow to change the request method (POST-2-GET and vice versa)
        return $this->redirect($url, 308);
    }
}
