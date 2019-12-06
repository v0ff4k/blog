<?php

namespace ApiBundle\EventListener;

use AppBundle\Helper\UserHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionSubscriber implements EventSubscriberInterface
{

    /**
     * @param \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent $event
     * @throws \Exception
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();

        UserHelper::getLogg()->error(
            'Kernel exception: ' .
            ' file:' . $e->getFile() .
            ', line:' . $e->getLine() .
            ', code:' . $e->getCode() .
            ', message:' . $e->getMessage()
        );

        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
        if (UserHelper::isDev()) {
            $problem = ['error' => [
                'statusCode' => $statusCode,
                'exStatusCode' => $e->getStatusCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'message' => $e->getMessage()
            ]];
        } else {
            $problem = ['error' => ['code' => $statusCode, 'message' => $e->getMessage(), 'etc' => 'value']];
        }

        $response = new JsonResponse($problem, $statusCode);
        $response->headers->set('Content-Type', 'application/problem+json');
        $event->setResponse($response);
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::EXCEPTION => 'onKernelException'
        );
    }

}