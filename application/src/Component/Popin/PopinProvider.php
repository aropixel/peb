<?php

namespace App\Component\Popin;

use App\Entity\Popin;
use App\Repository\PopinRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Choisit la popin à afficher sur la page courante.
 */
class PopinProvider
{
    public function __construct(
        private readonly PopinRepository $popinRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function getCurrentPopin(): ?Popin
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return null;
        }

        $queryString = $request->getQueryString();
        $currentUrl = $request->getPathInfo() . (null !== $queryString ? '?' . $queryString : '');

        foreach ($this->popinRepository->findActive() as $popin) {
            if ($popin->isDisplayAll()) {
                return $popin;
            }

            foreach ($popin->getUrls() as $pattern) {
                if (self::matches($currentUrl, $pattern)) {
                    return $popin;
                }
            }
        }

        return null;
    }

    /**
     * Compare l'URL courante (chemin + query string) à un motif saisi en admin :
     * chemin relatif ou URL absolue, avec le joker « * » éventuel.
     */
    public static function matches(string $currentUrl, string $pattern): bool
    {
        $pattern = trim($pattern);
        if ('' === $pattern) {
            return false;
        }

        // URL absolue (avec ou sans schéma) : on ne garde que le chemin et la query string.
        if (str_contains($pattern, '://') || preg_match('#^[\w-]+(\.[\w-]+)+(/|$)#', $pattern)) {
            $parts = parse_url(str_contains($pattern, '://') ? $pattern : 'https://' . $pattern);
            if (false === $parts) {
                return false;
            }
            $pattern = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        }

        if (!str_starts_with($pattern, '/') && !str_starts_with($pattern, '*')) {
            $pattern = '/' . $pattern;
        }

        if (str_contains($pattern, '*')) {
            $regex = str_replace('\*', '.*', preg_quote($pattern, '#'));

            return 1 === preg_match('#^' . $regex . '$#', $currentUrl);
        }

        return rtrim($currentUrl, '/') === rtrim($pattern, '/');
    }
}
