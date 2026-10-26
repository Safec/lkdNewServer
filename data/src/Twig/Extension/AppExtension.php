<?php

/** @noinspection PhpUnused */

namespace App\Twig\Extension;

use App\Twig\Runtime\AppExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // If your filter generates SAFE HTML, you should add a third
            // parameter: ['is_safe' => ['html']]
            // Reference: https://twig.symfony.com/doc/3.x/advanced.html#automatic-escaping
            new TwigFilter('phone', [AppExtensionRuntime::class, 'phoneFormat']),
            new TwigFilter('linkify', [$this, 'linkify'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('phone', [AppExtensionRuntime::class, 'phoneFormat']),
            new TwigFunction('plural', [AppExtensionRuntime::class, 'plural']),
        ];
    }

    public function linkify(?string $text): string
    {
        $text = $text ?? '';
        // Регулярное выражение для поиска URL-адресов
        $pattern = '/(https?:\/\/[^\s]+)/';

        // Замена URL на ссылки
        $replacement = '<a href="$1" target="_blank" style="text-decoration:underline;">$1</a>';

        return preg_replace($pattern, $replacement, $text);
    }
}
