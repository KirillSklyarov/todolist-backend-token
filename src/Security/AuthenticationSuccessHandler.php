<?php


namespace App\Security;


use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class AuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var ParameterBagInterface
     */
    private $bag;

    public function __construct(TokenRepository $tokenRepository,
                                UserRepository $userRepository,
                                ParameterBagInterface $bag)
    {
        $this->tokenRepository = $tokenRepository;
        $this->userRepository = $userRepository;
        $this->bag = $bag;
    }

    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @param Request $request
     * @param TokenInterface $sfToken
     * @return JsonResponse
     * @throws \Exception
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $sfToken)
    {
        $now = new \DateTime();

        /** @var User $user */
        $user = $sfToken->getUser();
        $token = (new Token())
            ->setValue(Uuid::uuid4()->toString())
            ->setCreatedAt($now)
            ->setLastEnterAt($now);
        $user->addToken($token);
        $user->setCurrentToken($token);
//        $this->tokenRepository->create($token);
        $this->userRepository->update($user);
        $data = [
            'success' => true,
            'message' => 'oh, yeah!',
        ];
        $response = new JsonResponse($data);
        $ttl = $this->bag->get('token.registered.ttl');
        $expireAt = clone $now;
        $expireAt->add(new \DateInterval($ttl));
        $cookie = new Cookie('token', $token->getValue(), $expireAt);
        $response->headers->setCookie($cookie);

        return $response;
    }
}