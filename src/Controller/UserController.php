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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validation;


/**
 * Class UserController
 * @package App\Controller
 * @Route("/api/v1/user")
 */
class UserController extends AbstractController
{
    public function validate(array $input): ConstraintViolationListInterface
    {
        $validator = Validation::createValidator();
        $collection = [
            'username' => [
                new Assert\Length([
                    'min' => 2,
                    'max' => 32,
                ]),
                new Assert\Regex([
                    'pattern' => '/^[\w.\-]+$/',
                ])
            ],
            'password' => [
                new Assert\Length([
                    'max' => 64,
                ]),
                new Assert\Regex([
                    'pattern' => '/^[\w!@#$%^&*()<>\-=+.,.?]+$/',
                ])
            ]
        ];
        $constraint = new Assert\Collection($collection);
        $violations = $validator->validate($input, $constraint);

        return $violations;
    }

    /**
     * @Route("/init", name="user_init", methods={"POST", "OPTIONS"})
     * @param EntityManagerInterface $em
     * @param UserRepository $userRepository
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function init(EntityManagerInterface $em, UserRepository $userRepository)
    {
        $now = new \DateTime();
        /** @var UserRepository $repository */
//        $repository = $em->getRepository(User::class);
        $token = (new Token())
            ->setValue(Uuid::uuid4()->toString())
            ->setCreatedAt($now)
            ->setLastEnterAt($now);
        $user = (new User())
            ->setCreatedAt($now)
            ->setUsername(Uuid::uuid4()->toString())
            ->addToken($token);
        $user->addToken($token);
        $userRepository->create($user);
        $response = new JsonResponse();
        $ttl = $this->getParameter('token.unregistered.ttl');
        $expire = clone $now;
        $expire->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expire);
        $response->headers->setCookie($cookie);
        return $response;
    }

    // TODO register

    /**
     * @Route("/register", name="user_regiter", methods={"POST", "OPTIONS"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     *
     * @throws \Exception
     */
    public function register(Request $request,
                             UserRepository $userRepository,
                             UserPasswordEncoderInterface $encoder)
    {
        /** @var User $user */
        $user = $this->getUser();
        $input = [
            'username' => $request->get('username'),
            'password' => $request->get('password'),
        ];
        $errors = $this->validate($input);
        if (\count($errors) > 0) {
            throw new BadRequestHttpException();
        }
        $existentUser = $userRepository->findOneBy(['username' => $input['username']]);
        if ($existentUser) {
            throw new ConflictHttpException();
        }
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('user-agent');
        $now = new \DateTime();
        $token = (new Token())
            ->setValue(Uuid::uuid4()->toString())
            ->setCreatedAt($now)
            ->setLastEnterAt($now)
            ->setIp(\mb_substr($ip, 0, 39))
            ->setUserAgent(\mb_substr($userAgent, 0, 255));
        $encoded = $encoder->encodePassword($user, $input['password']);

        $user->clearTokens()
            ->addToken($token)
            ->setUsername($input['username'])
            ->setPassword($encoded)
            ->removeRole('ROLE_UNREGISTERED_USER')
            ->addRole('ROLE_REGISTERED_USER')
            ->setPermanent(true)
            ->setRegisteredAt($now)
            ->setUpdatedAt($now)
        ;
        $userRepository->update($user);
        $response = new JsonResponse();
        $ttl = $this->getParameter('token.unregistered.ttl');
        $expire = clone $now;
        $expire->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expire);
        $response->headers->setCookie($cookie);
        return $response;
    }
}
