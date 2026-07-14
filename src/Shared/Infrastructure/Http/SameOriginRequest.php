<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * CSRF guard for fetch/XHR-only endpoints: each accepted signal proves the
 * request came from our own origin and none can be forged by a cross-site
 * form or script.
 */
final class SameOriginRequest
{
    public static function isSatisfiedBy(Request $request): bool
    {
        return 'same-origin' === $request->headers->get('Sec-Fetch-Site')
            || $request->headers->get('Origin') === $request->getSchemeAndHttpHost()
            || 'XMLHttpRequest' === $request->headers->get('X-Requested-With');
    }
}
