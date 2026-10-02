<?php

namespace App\Tests\Component\Popin;

use App\Component\Popin\PopinProvider;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PopinProviderTest extends TestCase
{
    #[DataProvider('provideMatchesCases')]
    public function testMatches(string $currentUrl, string $pattern, bool $expected): void
    {
        self::assertSame($expected, PopinProvider::matches($currentUrl, $pattern));
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function provideMatchesCases(): iterable
    {
        yield 'chemin exact' => ['/spectacles', '/spectacles', true];
        yield 'slash final ignoré' => ['/spectacles/', '/spectacles', true];
        yield 'sans slash initial' => ['/spectacles', 'spectacles', true];
        yield 'autre page' => ['/contacts', '/spectacles', false];
        yield 'préfixe sans joker' => ['/nova-2025-2026', '/nova', false];
        yield 'joker' => ['/nova-2025-2026', '/nova*', true];
        yield 'joker sur tout' => ['/contacts', '*', true];
        yield 'URL absolue' => ['/nova', 'https://pebarre.com/nova', true];
        yield 'URL sans schéma' => ['/nova', 'www.pebarre.com/nova', true];
        yield 'domaine seul = accueil' => ['/', 'https://pebarre.com', true];
        yield 'query string' => ['/spectacles?a=1', '/spectacles?a=1', true];
        yield 'query string différente' => ['/spectacles?a=2', '/spectacles?a=1', false];
        yield 'point non interprété en regex' => ['/fooxhtml', '/foo.html*', false];
        yield 'motif vide' => ['/', '  ', false];
    }
}
