<?php

namespace App\Service;

use InvalidArgumentException;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

use function Symfony\Component\Translation\t;

class PhoneParser
{
    /**
     * Нормализовать номер телефона к формату E164 (пример: +79990000000).
     *
     * @throws InvalidArgumentException
     */
    public static function normalizeOrExcept(string $phone): string
    {
        try {
            $parser = PhoneNumberUtil::getInstance();
            $parsedPhone = $parser->parse($phone, 'RU');
            if (!$parser->isValidNumberForRegion($parsedPhone, 'RU')) {
                throw new InvalidArgumentException(t('Invalid phone number.')->getMessage());
            }

            return $parser->format($parsedPhone, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            throw new InvalidArgumentException(t('Invalid phone number.')->getMessage());
        }
    }

    public static function normalize(string $phone): string
    {
        try {
            return self::normalizeOrExcept($phone);
        } catch (InvalidArgumentException) {
            return $phone;
        }
    }

    public static function isValid(string $phone): bool
    {
        try {
            $parser = PhoneNumberUtil::getInstance();
            $parsedPhone = $parser->parse($phone, 'RU');
            return $parser->isValidNumberForRegion($parsedPhone, 'RU');
        } catch (NumberParseException) {
            return false;
        }
    }

    public static function format(string $phone): string
    {
        try {
            $parser = PhoneNumberUtil::getInstance();
            $parsedPhone = $parser->parse($phone, 'RU');
            $formatted = $parser->format($parsedPhone, PhoneNumberFormat::NATIONAL);
            return preg_replace('/^8/u', '+7', $formatted);
        } catch (NumberParseException) {
            return $phone;
        }
    }
}