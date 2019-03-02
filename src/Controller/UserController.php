<?php

namespace App\Controller;

use App\Entity\Token;
use App\Entity\User;
use App\Model\ApiResponse;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
     * @param Request $request
     * @param UserRepository $userRepository
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Exception
     */
    public function init(Request $request, UserRepository $userRepository)
    {
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('user-agent');
        $token = (new Token())
            ->setIp($ip)
            ->setUserAgent($userAgent);
        $user = (new User())
            ->setUsername(Uuid::uuid4()->toString())
            ->addToken($token);
        $userRepository->create($user);
        $response = new ApiResponse();
        $ttl = $this->getParameter('token.unregistered.ttl');
        $createdAt = $token->getCreatedAt();
        $expire = clone $createdAt;
        $expire->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expire);
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * @Route("/register", name="user_regiter", methods={"POST", "OPTIONS"})
     * @param Request $request
     * @param UserRepository $userRepository
     * @param UserPasswordEncoderInterface $encoder
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function register(Request $request,
                             UserRepository $userRepository,
                             UserPasswordEncoderInterface $encoder)
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($user->getPermanent()) {
            throw new AccessDeniedHttpException('You are already registered');
        }
        $input = [
            'username' => $request->get('username'),
            'password' => $request->get('password'),
        ];
        $errors = $this->validate($input);
        if (\count($errors) > 0) {
            throw new BadRequestHttpException((string)$errors);
        }
        $existentUser = $userRepository->findOneBy(['username' => $input['username']]);
        if ($existentUser) {
            throw new ConflictHttpException();
        }
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('user-agent');
        $token = (new Token())
            ->setIp($ip)
            ->setUserAgent($userAgent);
        $encoded = $encoder->encodePassword($user, $input['password']);
        $lastEnterAt = $token->getLastEnterAt();

        $user->clearTokens()
            ->addToken($token)
            ->setUsername($input['username'])
            ->setPassword($encoded)
            ->removeRole('ROLE_UNREGISTERED_USER')
            ->addRole('ROLE_REGISTERED_USER')
            ->setPermanent(true)
            ->setRegisteredAt($lastEnterAt)
            ->setUpdatedAt($lastEnterAt);
        $userRepository->update($user);
        $response = new ApiResponse();
        $ttl = $this->getParameter('token.unregistered.ttl');
        $expire = clone $lastEnterAt;
        $expire->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expire);
        $response->headers->setCookie($cookie);
        return $response;
    }

    /**
     * @Route("/logout", name="user_logout", methods={"POST", "OPTIONS"})
     * @param TokenRepository $tokenRepository
     * @return JsonResponse
     * @throws \Exception
     */
    public function logout(TokenRepository $tokenRepository)
    {
        /** @var User $user */
        $user = $this->getUser();
        $token = $user->getCurrentToken();
        $tokenRepository->delete($token);
        $response = new ApiResponse();
        $response->headers->clearCookie('token');
        return $response;
    }
}
