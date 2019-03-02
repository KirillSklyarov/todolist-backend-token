<?php

namespace App\EventListener;

use App\Model\ApiResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class ExceptionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->container = $container;
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $message = $exception->getMessage();
        $context = [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace(),
        ];
        $this->logger->error($message, $context);
        $apiResponse = new ApiResponse(null, false, '');
        if ($exception instanceof HttpExceptionInterface) {
            $apiResponse->setStatusCode($exception->getStatusCode());
            $apiResponse->setMessage($message);
        } else {
            $apiResponse->setStatusCode(500);
            if ($this->container->get('kernel')->getEnvironment() === 'dev') {
                $apiResponse->setMessage($message);
            }
        }
        $event->setResponse($apiResponse);
    }
}