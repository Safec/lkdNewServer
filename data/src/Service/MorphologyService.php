<?php

namespace App\Service;

use morphos\Cases;
use morphos\Russian;
use Psr\Log\LoggerInterface;
use Throwable;

class MorphologyService
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public function genetiveFullName(string $fullName): string|false
    {
        try {
            return Russian\inflectName($fullName, Cases::GENITIVE);
        } catch (Throwable $error) {
            $this->logger->notice($error->getMessage() ?: get_class($error));
            $this->logger->notice('Не удалось сгенерировать фамилию в родительном падеже для {fullName}.', ['fullName' => $fullName]);
            return $fullName;
        }
    }

    public function genetiveFirstWord(string $phrase): string
    {
        try {
            $words = explode(' ', $phrase);
            $words[0] = Russian\NounDeclension::getCase($words[0], Cases::GENITIVE);

            return implode(' ', $words);
        } catch (Throwable $error) {
            $this->logger->notice($error->getMessage() ?: get_class($error));
            $this->logger->notice('Не удалось сгенерировать первое слово в родительном падеже для "{phrase}".', ['phrase' => $phrase]);
            return $phrase;
        }
    }

    public function fullNameWithInitials(string $fullName): string
    {
        $words = array_filter(explode(' ', $fullName), 'strlen');
        $surname = $words[0] ?? '';
        $name = isset($words[1]) ? mb_substr($words[1], 0, 1) . '.' : '';
        $patronymic = isset($words[2]) ? mb_substr($words[2], 0, 1) . '.' : '';

        return implode(' ', array_filter([$surname, $name, $patronymic]));
    }
}