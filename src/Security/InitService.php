<?php


namespace App\Security;


use App\Entity\Token;
use App\Entity\User;
use App\Repository\TokenRepository;
use App\Repository\UserRepository;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class InitService
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var ParameterBagInterface
     */
    private $bag;

    /**
     * @var TokenRepository
     */
    private $tokenRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(RequestStack $requestStack,
                                ParameterBagInterface $bag,
                                TokenRepository $tokenRepository,
                                UserRepository $userRepository)
    {
        $this->requestStack = $requestStack;
        $this->bag = $bag;
        $this->tokenRepository = $tokenRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @return User
     * @throws \Exception
     */
    public function initUser(): User
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request->getClientIp();
        $userAgent = $request->headers->get('user-agent');
        $token = (new Token())
            ->setIp($ip)
            ->setUserAgent($userAgent);
        $user = (new User())
            ->setUsername(Uuid::uuid4()->toString())
            ->addToken($token)
            ->setCurrentToken($token);

        $this->userRepository->create($user);

        return $user;
    }

    /**
     * @return User|null
     * @throws \Exception
     */
    public function getUser(): ?User
    {
        $request = $this->requestStack->getCurrentRequest();
        $cookies = $request->cookies;
        if (!$cookies->has('token') || !Uuid::isValid($cookies->get('token'))) {
            return null;
        }
        $credentials = $cookies->get('token');

        $token = $this->tokenRepository->findOneBy(['value' => $credentials]);
        if ($token === null) {
            return null;
        }
        $user = $token->getUser();
        if ($user === null) {
            // TODO Проверить логи doctrine, если удаление завершится неудачей
            $this->tokenRepository->delete($token);

            return null;
        }
        if ($this->checkToken($token) === false) {
            return null;
        }
        $token->setLastEnterAt(new \DateTime());
        $this->tokenRepository->update($token);
        $user->setCurrentToken($token);

        return $user;
    }

    /**
     * @param Token $token
     * @return bool
     * @throws \Exception
     */
    public function checkToken(Token $token): bool
    {
        $user = $token->getUser();
        $ttl = $this->bag->get($user->getPermanent() ? 'token.registered.ttl' : 'token.unregistered.ttl');
        $lastEnterAt = $token->getLastEnterAt();
        $expiredAt = clone $lastEnterAt;
        $expiredAt->add(new \DateInterval($ttl));
        $now = new \DateTime();
        if ($expiredAt <= $now) {
            $this->tokenRepository->delete($token);
            return false;
        }

        return true;
    }
}
