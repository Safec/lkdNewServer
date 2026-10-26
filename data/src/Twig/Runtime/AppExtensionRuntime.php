<?php

namespace App\Twig\Runtime;

use App\Service\PhoneParser;
use Twig\Extension\RuntimeExtensionInterface;

class AppExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct()
    {
        // Inject dependencies if needed
    }

    public function phoneFormat(mixed $phone): string
    {
        return PhoneParser::format((string)$phone);
    }

    public function plural(mixed $number, string $one, string $three, string $five): string
    {
        $number = abs(is_numeric($number) ? (int)$number : 0);

        $number %= 100;
        if ($number >= 5 && $number <= 20) {
            return $five;
        }

        $number %= 10;
        if ($number == 1) {
            return $one;
        }

        if ($number >= 2 && $number <= 4) {
            return $three;
        }

        return $five;
    }
}
