<?php

/** @noinspection PhpUnused */

namespace App\Security;

use App\Service\CaptchaService;
use App\Service\PhoneParser;
use InvalidArgumentException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class PasswordAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public array $options = [
        'username_parameter' => '_username',
        'password_parameter' => '_password',
        'csrf_parameter' => '_csrf_token',
        'csrf_token_id' => 'authenticate',
        'captcha_csrf_parameter' => '_captcha_csrf_token',
        'captcha_csrf_token_id' => '_captcha',
    ];

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly RateLimiterFactory $authenticateLimiter,
        private readonly RateLimiterFactory $authenticateWithoutCaptchaLimiter,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly HttpClientInterface $httpClient,
        private readonly CaptchaService $captchaService,
        array $options = [],
    ) {
        $this->options = array_merge($this->options, $options);
    }

    public function supports(Request $request): bool
    {
        return (
            $request->isMethod(Request::METHOD_POST) &&
            $request->request->has((string)$this->options['username_parameter']) &&
            $request->request->has((string)$this->options['password_parameter'])
        );
    }

    public function authenticate(Request $request): Passport
    {
        $this->checkRateLimitOrExcept(
            $request,
            $this->authenticateLimiter,
            'Вы превысили лимит попыток входа. Попробуйте через %d сек.'
        );

        $this->checkCaptchaOrExcept($request, $this->authenticateWithoutCaptchaLimiter);

        $username = (string)$request->request->get((string)$this->options['username_parameter'], '');

        $ipRateLimit = $this->authenticateLimiter->create($request->getClientIp())->consume();
        $usernameRateLimit = $this->authenticateLimiter->create($username)->consume();
        if (!$ipRateLimit->isAccepted() || !$usernameRateLimit->isAccepted()) {
            throw new CustomUserMessageAuthenticationException('Слишком много попыток входа. Попробуйте позже.');
        }

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        if (PhoneParser::isValid($username)) {
            $username = PhoneParser::normalize($username);
        }

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials((string)$request->request->get((string)$this->options['password_parameter'], '')),
            [
                new CsrfTokenBadge(
                    (string)$this->options['csrf_token_id'],
                    (string)$request->request->get((string)$this->options['csrf_parameter'], '')
                ),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('app.home'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->urlGenerator->generate('auth.login_with_password'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('auth.login_with_password');
    }

    /**
     * @throws CustomUserMessageAuthenticationException
     */
    private function checkRateLimitOrExcept(Request $request, RateLimiterFactory $rateLimiter, string $error = ''): void
    {
        $ipRateLimit = $rateLimiter->create($request->getClientIp())->consume();
        if (!$ipRateLimit->isAccepted()) {
            throw new CustomUserMessageAuthenticationException(
                sprintf(
                    $error ?: 'Слишком много запросов. Попробуйте снова через %d сек.',
                    $ipRateLimit->getRetryAfter()->getTimestamp() - time()
                )
            );
        }

        $username = (string)$request->request->get((string)$this->options['username_parameter'], '');
        if (empty($username)) {
            return;
        }

        $usernameRateLimiter = $rateLimiter->create($username)->consume();
        if (!$usernameRateLimiter->isAccepted()) {
            throw new CustomUserMessageAuthenticationException(
                sprintf(
                    $error ?: 'Слишком много запросов. Попробуйте снова через %d сек.',
                    $usernameRateLimiter->getRetryAfter()->getTimestamp() - time()
                )
            );
        }
    }

    /**
     * @throws CustomUserMessageAuthenticationException
     */
    private function checkCaptchaOrExcept(Request $request, RateLimiterFactory $captchaRateLimiter): void
    {
        $hCaptchaResponse = $request->request->get('h-captcha-response');

        // Когда в форме не выводится hCaptcha, то генерируется отдельный CSRF токен.
        // Это позволяет понять, что hCaptcha специально не выводилась и не нужно проверять её.
        if (empty($hCaptchaResponse)) {
            $captchaRateLimiter->create($request->getClientIp())->consume();
            $username = (string)$request->request->get((string)$this->options['username_parameter'], '');
            if (!empty($username)) {
                $captchaRateLimiter->create($username)->consume();
            }

            $token = new CsrfToken(
                (string)$this->options['captcha_csrf_token_id'],
                (string)$request->request->get((string)$this->options['captcha_csrf_parameter'], '')
            );

            if (!$this->csrfTokenManager->isTokenValid($token)) {
                throw new CustomUserMessageAuthenticationException('Недействительный токен CSRF.');
            } else {
                return;
            }
        }

        try {
            if ($this->captchaService->verify($hCaptchaResponse)) {
                $this->resetCaptchaRateLimiters($request);
            } else {
                throw new InvalidArgumentException();
            }
        } catch (Throwable) {
            throw new CustomUserMessageAuthenticationException('Ошибка проверки капчи.');
        }
    }

    private function resetCaptchaRateLimiters(Request $request): void
    {
        $this->authenticateWithoutCaptchaLimiter->create($request->getClientIp())->reset();
        $username = (string)$request->request->get((string)$this->options['username_parameter'], '');
        if (!empty($username)) {
            $this->authenticateWithoutCaptchaLimiter->create($username)->reset();
        }
    }
}
