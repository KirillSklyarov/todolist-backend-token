<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/init", name="user_init", methods={"POST", "OPTIONS"})
     * @param EntityManagerInterface $em
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function init(EntityManagerInterface $em)
    {
        $now = new \DateTime();
        /** @var UserRepository $repository */
        $repository = $em->getRepository(User::class);
        $token = (new Token())
            ->setValue(Uuid::uuid4()->toString())
            ->setCreatedAt($now)
            ->setLastLoginAt($now);
        $user = (new User())
            ->setUsername(Uuid::uuid4()->toString())
            ->addToken($token);
        $user->addToken($token);
        $repository->create($user);
        $response = new JsonResponse();
        $ttl = $this->getParameter('token.unregistred.ttl');
        $expire = clone $now;
        $expire->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expire);
        $response->headers->setCookie($cookie);
        return $response;
    }
}
