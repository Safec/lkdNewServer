<?php

/** @noinspection PhpUnused */

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\CaptchaService;
use App\Service\PhoneParser;
use Exception;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Throwable;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\RegistrationUtm;

class OneTimeCodeAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public array $options = [
        'phone_parameter' => '_phone',
        'code_parameter' => '_code',
        'csrf_parameter' => '_csrf_token',
        'csrf_token_id' => '_otp',
        'captcha_csrf_parameter' => '_captcha_csrf_token',
        'captcha_csrf_token_id' => '_captcha',
        'otp_session_key' => '_otp',
        'otp_code_digits' => 4,
        'otp_code_lifetime' => 120,
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RouterInterface $router,
        private readonly NotifierInterface $notifier,
        private readonly RateLimiterFactory $sendSmsCodeLimiter,
        private readonly RateLimiterFactory $sendSmsCodeWithoutCaptchaLimiter,
        private readonly RateLimiterFactory $authenticateLimiter,
        private readonly RateLimiterFactory $authenticateWithoutCaptchaLimiter,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly CaptchaService $captchaService,
        private EntityManagerInterface $em,
        private RequestStack $requestStack,
        private readonly array $defaultRoles = [User::ROLE_DESIGNER],
        array $options = [],
    ) {
        $this->options = array_merge($this->options, $options);
    }

    public function supports(Request $request): ?bool
    {
        return (
            $request->isMethod('POST') &&
            $request->request->has((string)$this->options['phone_parameter']) &&
            $request->request->has((string)$this->options['code_parameter'])
        );
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('auth.login_with_sms_code'));
    }

    /**
     * Сгенерировать новый код, сохранить в сессии и отправить по SMS.
     *
     * @throws Exception
     */
    public function sendNewCode(Request $request, string $phone): void
    {
        $this->checkCaptchaOrExcept($request, $this->sendSmsCodeWithoutCaptchaLimiter);

        $this->checkRateLimitOrExcept(
            $request,
            $this->sendSmsCodeLimiter,
            'Сейчас нельзя отправить SMS-код. Попробуйте через %d сек.'
        );

        $phone = PhoneParser::normalizeOrExcept($phone);

        $digits = (int)$this->options['otp_code_digits'];
        $code = sprintf('%0' . $digits . 'd', random_int(0, (10 ** $digits) - 1));
        $request->getSession()->set((string)$this->options['otp_session_key'], [
            'phone' => $phone,
            'code' => password_hash($code, PASSWORD_DEFAULT),
            'sent_at' => time(),
            'expire_at' => time() + (int)$this->options['otp_code_lifetime'],
            'attempts' => 0,
        ]);

        $defaultTextSms = "ваш код подтверждения для входа в ЛК Askona lkd.askona.ru";

        $notification = new Notification(sprintf('%s '.$defaultTextSms, $code), ['sms']);
        $recipient = new Recipient(phone: $phone);
        $this->notifier->send($notification, $recipient);
    }

    public function authenticate(Request $request): Passport
    {
        $this->checkCaptchaOrExcept($request, $this->authenticateWithoutCaptchaLimiter);

        $this->checkRateLimitOrExcept(
            $request,
            $this->authenticateLimiter,
            'Вы превысили лимит попыток входа. Попробуйте через %d сек.'
        );

        $phone = (string)$request->request->get((string)$this->options['phone_parameter']);
        $code = (string)$request->request->get((string)$this->options['code_parameter']);
        $csrfToken = (string)$request->request->get((string)$this->options['csrf_parameter']);

        return new Passport(
            new UserBadge(
                $phone,
                function (string $userIdentifier) {
                    $existUser = $this->userRepository->loadUserByIdentifier($userIdentifier);
                    if ($existUser instanceof UserInterface) {
                        return $existUser;
                    }

                    $newUser = new User();
                    $newUser->setPhone($userIdentifier);
                    $newUser->setRoles($this->defaultRoles);


                    // Сохраняем UTM из сессии
                    $session = $this->requestStack->getCurrentRequest()->getSession();
                    $utm = new RegistrationUtm();
                    $utm->setRelatedUser($newUser)
                        ->setUtmSource($session->get('utm_source'))
                        ->setUtmMedium($session->get('utm_medium'))
                        ->setUtmCampaign($session->get('utm_campaign'))
                        ->setUtmTerm($session->get('utm_term'))
                        ->setUtmContent($session->get('utm_content'))
                        ->setYmUid($session->get('ymClientId'));

                    if ($session->has('utm_source') || $session->has('utm_medium') || $session->has('utm_campaign')) {
                        $this->em->persist($newUser);
                        $this->em->persist($utm);
                        $this->em->flush();
                    }

                    // Очистка UTM из сессии
                    foreach (['utm_source','utm_medium','utm_campaign','utm_term','utm_content','ymClientId'] as $key) {
                        $session->remove($key);
                    }


                    return $newUser;
                }
            ),
            new CustomCredentials(
                function (mixed $credentials, UserInterface $user) use ($request) {
                    if ($user instanceof User) {
                        /** @var array|mixed $otp */
                        $otp = $request->getSession()->get((string)$this->options['otp_session_key']);
                        $phone = is_array($otp) && isset($otp['phone']) ? (string)$otp['phone'] : '';
                        $hash = is_array($otp) && isset($otp['code']) ? (string)$otp['code'] : '';
                        $expires = is_array($otp) && isset($otp['expire_at']) ? (int)$otp['expire_at'] : 0;

                        $isValidPhone = $phone === $user->getPhone();
                        if (!$isValidPhone) {
                            throw new CustomUserMessageAuthenticationException('Неверный номер телефона.');
                        }

                        $isVerifyCode = password_verify((string)$credentials, $hash);
                        if (!$isVerifyCode) {
                            throw new CustomUserMessageAuthenticationException('Неверный код подтверждения.');
                        }

                        $isExpiredCode = $expires < time();
                        if ($isExpiredCode) {
                            throw new CustomUserMessageAuthenticationException(
                                'Код подтверждения недействителен. Запросите новый код подтверждения.'
                            );
                        }

                        $session = $request->getSession();
                        $session->set('registration-sms_code-confirmed', true);

                         return true;
                    } else {
                        return false; // @codeCoverageIgnore
                    }
                },
                $code
            ),
            [
                new CsrfTokenBadge((string)$this->options['csrf_token_id'], $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $request->getSession()->remove((string)$this->options['otp_session_key']);

        $user = $token->getUser();
        if ($user instanceof User) {
            $this->userRepository->save($user)->flush();
        }

        return new RedirectResponse($this->router->generate('auth.login_with_sms_code'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        return new RedirectResponse($this->router->generate('auth.auth'));
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

        $phone = (string)$request->request->get((string)$this->options['phone_parameter'], '');
        if (empty($phone)) {
            return;
        }

        $phoneRateLimit = $rateLimiter->create($phone)->consume();
        if (!$phoneRateLimit->isAccepted()) {
            throw new CustomUserMessageAuthenticationException(
                sprintf(
                    $error ?: 'Слишком много запросов. Попробуйте снова через %d сек.',
                    $phoneRateLimit->getRetryAfter()->getTimestamp() - time()
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
            $phone = (string)$request->request->get((string)$this->options['phone_parameter'], '');

            /** @noinspection DuplicatedCode */
            if (!empty($phone)) {
                $captchaRateLimiter->create($phone)->consume();
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
        $this->sendSmsCodeWithoutCaptchaLimiter->create($request->getClientIp())->reset();
        $this->authenticateWithoutCaptchaLimiter->create($request->getClientIp())->reset();

        $phone = (string)$request->request->get((string)$this->options['phone_parameter'], '');
        if (!empty($phone)) {
            $this->sendSmsCodeWithoutCaptchaLimiter->create($phone)->reset();
            $this->authenticateWithoutCaptchaLimiter->create($phone)->reset();
        }
    }
}
