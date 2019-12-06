<?php

namespace ApiBundle\Controller;

use FOS\RestBundle\Controller\AbstractFOSRestController;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

class ApiBaseController extends AbstractFOSRestController
{

    const PAGE_CACHE_TIME = 200;//in msec

    /**
     * Check if the user logged in.
     *
     * @return boolean
     */
    public function isUserLoggedIn()
    {
        return $this
            ->container
            ->get('security.authorization_checker')
            ->isGranted('IS_AUTHENTICATED_FULLY');
    }

    /**
     * Extended method denyAccessUnlessGranted with auth fully
     *
     * @return void
     */
    public function denyAccessUnlessGrantedFully()
    {
        $message = $this->translate('http_error_403.description', [], 'messages');
        $this
            ->denyAccessUnlessGranted(
                'IS_AUTHENTICATED_FULLY',
                null,
                $message
            );
    }

    /**
     * Make translation, lightweight micro method for api.
     *
     * @param string $message
     * @param array $params
     * @param string $domain
     * @return string
     */
    protected function translate($message, $params = [], $domain = 'api')
    {
        /** @var \ApiBundle\Service\ApiService $apiService */
        $oApiService = $this->container->get('api.service.security');
        return $oApiService->translate($message, $params, $domain);
    }

    /**
     * getErrorsFromFormInterface - Get error returned from SecurityController
     *
     * @param \Symfony\Component\Form\FormInterface $form
     * @return array
     */
    protected function getErrorsFromFormInterface(FormInterface $form)
    {

        $errors = array();

        foreach ($form->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm) {
            if ($childForm instanceof FormInterface) {
                if ($childErrors = $this->getErrorsFromFormInterface($childForm)) {
                    $errors[$childForm->getName()] = $childErrors;
                }
            }
        }

        return $errors;
    }

    /**
     * Micro method for control all in one default response  with app/json+HTTP_OK
     *
     * @param $data
     * @param null $statusCode
     * @param array $headers
     * @param string $format
     * @param null $context
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function returnApiResponse($data, $statusCode = null, $headers = [], $format = 'json', $context = null)
    {
        $headersDefault = [
            'Content-Type' => 'application/json',
            'max_age'       => static::PAGE_CACHE_TIME,
            's_maxage'      => static::PAGE_CACHE_TIME,
            'public'        => true,
        ];

        $headers = (null === $headers or empty($headers) or !is_array($headers))
            ? $headersDefault
            : $headers;

        $statusCode = (empty($statusCode)) ? Response::HTTP_OK : $statusCode;
        $serializedData = $this->serialize($data, $format, $context);

        return new Response($serializedData, $statusCode, $headers);
    }

    /**
     * @param $data
     * @param string $format
     * @param null $context
     * @return mixed|string|array
     */
    protected function serialize($data, $format = 'json', $context = null)
    {
        if (null !== $context && !empty($context) && is_array($context)) {
            $context = SerializationContext::create()->setGroups($context);
        }//also    $context instanceof SerializationContext or === null

        return $this
            ->container
            ->get('jms_serializer')
            ->serialize($data, $format, $context);
    }
}
