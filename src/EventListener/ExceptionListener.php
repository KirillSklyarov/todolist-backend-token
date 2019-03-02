<?php

namespace App\EventListener;

use App\Model\ApiResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        // TODO logger
        $exception = $event->getException();
        $apiResponse = new ApiResponse(null, false, '');

        if ($exception instanceof HttpExceptionInterface) {
            $apiResponse->setStatusCode($exception->getStatusCode());
            $apiResponse->setMessage($exception->getMessage());
        } else {
            $apiResponse->setStatusCode(500);
            if ($this->container->get('kernel')->getEnvironment() === 'dev') {
                $apiResponse->setMessage($exception->getMessage());
            }
        }
        $event->setResponse($apiResponse);
    }
}