<?php

/** @noinspection PhpUnused */

namespace App\Controller;

use App\Security\OneTimeCodeAuthenticator;
use App\Security\PasswordAuthenticator;
use App\Service\PhoneParser;
use DateTime;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AuthController extends AbstractController
{
    public function __construct(
        private readonly OneTimeCodeAuthenticator $oneTimeCodeAuthenticator,
        private readonly PasswordAuthenticator $passwordAuthenticator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/auth', name: 'auth')]
    public function __invoke(): Response
    {
        return $this->redirectToRoute('auth.login_with_sms_code');
    }

    #[Route('/auth/login', name: 'auth.login_with_sms_code')]
    public function loginWithSmsCode(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        Session $session,
        RateLimiterFactory $sendSmsCodeWithoutCaptchaLimiter,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.home');
        }

        $ipRateLimit = $sendSmsCodeWithoutCaptchaLimiter->create($request->getClientIp())->consume(0);
        $useCaptcha = !$ipRateLimit->isAccepted();

        return $this->render('auth/login_with_sms_code.html.twig', [
            'last_phone' => $session->get('phone') ?: $authenticationUtils->getLastUsername(),
            'options' => $this->oneTimeCodeAuthenticator->options,
            'use_captcha' => $useCaptcha,
        ]);
    }

    #[Route('/auth/manager', name: 'auth.login_with_password')]
    public function loginWithPassword(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        RateLimiterFactory $authenticateWithoutCaptchaLimiter,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app.home');
        }

        $ipRateLimit = $authenticateWithoutCaptchaLimiter->create($request->getClientIp())->consume(0);
        $useCaptcha = !$ipRateLimit->isAccepted();

        $authError = $authenticationUtils->getLastAuthenticationError();
        if ($authError instanceof Exception) {
            $this->addFlash('error', $this->translator->trans($authError->getMessage(), domain: 'security', locale: $request->getLocale()));
        }

        return $this->render('auth/login_with_password.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'options' => $this->passwordAuthenticator->options,
            'use_captcha' => $useCaptcha,
        ]);
    }

    #[Route('/auth/send-code', name: 'auth.send_code', methods: ['POST'])]
    public function sendCode(
        Request $request,
        Session $session,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): Response {
        try {
            $csrfToken = new CsrfToken(
                (string)$this->oneTimeCodeAuthenticator->options['csrf_token_id'],
                (string)$request->request->get((string)$this->oneTimeCodeAuthenticator->options['csrf_parameter'])
            );

            if (!$csrfTokenManager->isTokenValid($csrfToken)) {
                throw new InvalidArgumentException('Ошибка проверки CSRF-токена.');
            }

            $phone = (string)$request->request->get((string)$this->oneTimeCodeAuthenticator->options['phone_parameter'], '');
            $session->set('phone', $phone);
            $phone = PhoneParser::normalizeOrExcept($phone);

            $this->oneTimeCodeAuthenticator->sendNewCode($request, $phone);
            $this->addFlash('success', "Код отправлен в SMS на номер $phone.");

            return $this->redirectToRoute('auth.auth');
        } catch (Exception $e) {
            $this->addFlash(
                'error',
                $this->translator->trans(
                    $e->getMessage(),
                    domain: 'security',
                    locale: $request->getLocale()
                ) ?: 'Ошибка отправки кода.'
            );

            return $this->redirectToRoute('auth.login_with_sms_code');
        }
    }

    #[Route('/auth/auth', name: 'auth.auth')]
    public function auth(
        Request $request,
        Session $session,
        AuthenticationUtils $authenticationUtils,
        RateLimiterFactory $authenticateWithoutCaptchaLimiter,
    ): Response
    {
        /** @var array|mixed $otp */
        $otp = $session->get((string)$this->oneTimeCodeAuthenticator->options['otp_session_key']);
        $phone = is_array($otp) && isset($otp['phone']) ? (string)$otp['phone'] : '';
        $sentAt = is_array($otp) && isset($otp['sent_at']) ? (int)$otp['sent_at'] : 0;

        $resendTimer = 0;
        if ($sentAt > 0) {
            $sentAtDateTime = DateTime::createFromFormat('U', (string)$sentAt);
            $smsSendCodeInterval = $this->getParameter('rate_limiter.sms_send_code.interval');
            if (is_string($smsSendCodeInterval)) {
                $resendDateTime = $sentAtDateTime->modify('+' . $smsSendCodeInterval);
                $resendTimer = max($resendDateTime->getTimestamp() - time(), 0);
            }
        }

        $authError = $authenticationUtils->getLastAuthenticationError();
        if ($authError instanceof Exception) {
            $this->addFlash(
                'error',
                $this->translator->trans($authError->getMessage(), domain: 'security', locale: $request->getLocale())
            );
        }

        $ipRateLimit = $authenticateWithoutCaptchaLimiter->create($request->getClientIp())->consume(0);
        $phoneRateLimit = $authenticateWithoutCaptchaLimiter->create($phone)->consume(0);
        $useCaptcha = !$ipRateLimit->isAccepted() || !$phoneRateLimit->isAccepted();

        return $this->render('auth/auth.html.twig', [
            'phone' => $phone,
            'options' => $this->oneTimeCodeAuthenticator->options,
            'resend_timer' => $resendTimer,
            'use_captcha' => $useCaptcha,
        ]);
    }

    /**
     * @codeCoverageIgnore This method can be blank - it will be intercepted by the logout key on your firewall.
     * @see https://symfony.com/doc/current/security.html#logging-out
     */
    #[Route('/auth/logout', name: 'auth.logout', methods: ['GET'])]
    public function logout(): void
    {
    }
}
