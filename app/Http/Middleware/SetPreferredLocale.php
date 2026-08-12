<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;

class SetPreferredLocale
{
    public function handle($request, Closure $next)
    {
        $locales = ['en', 'pt_BR'];

        $language = $request->getPreferredLanguage($locales);

        app()->setLocale($language);

        return $next($request);
    }
}
