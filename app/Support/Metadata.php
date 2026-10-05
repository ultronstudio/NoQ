<?php

namespace App\Support;

use Illuminate\Http\Request;

class Metadata
{
    public function url(string $path = ''): string
    {
        return rtrim(config()->string('app.url'), '/').'/'.ltrim($path, '/');
    }

    public function isPublic(Request $request): bool
    {
        return in_array($request->route()?->getName(), config()->array('metadata.public_routes'), true);
    }

    public function robots(Request $request): string
    {
        return config()->boolean('metadata.indexable') && $this->isPublic($request)
            ? 'index, follow, max-image-preview:large'
            : 'noindex, nofollow';
    }

    /**
     * @return array<string, mixed>
     */
    public function page(Request $request, string $title = '', string $description = ''): array
    {
        $name = config()->string('metadata.name');
        $title = trim(strip_tags($title));
        $title = $title !== '' ? $title.' | '.$name : $name;
        $description = trim(strip_tags($description)) ?: config()->string('metadata.description');
        $canonical = $this->url(trim($request->getPathInfo(), '/'));
        $public = $this->isPublic($request);
        $graph = [];

        if ($public) {
            $graph[] = [
                '@type' => 'WebPage',
                '@id' => $canonical.'#webpage',
                'url' => $canonical,
                'name' => $title,
                'description' => $description,
                'inLanguage' => config()->string('metadata.language'),
                'isPartOf' => ['@id' => $this->url().'#website'],
            ];

            if ($request->routeIs('home')) {
                $graph[] = [
                    '@type' => 'WebSite',
                    '@id' => $this->url().'#website',
                    'url' => $this->url(),
                    'name' => $name,
                    'description' => $description,
                    'inLanguage' => config()->string('metadata.language'),
                    'about' => ['@id' => $this->url().'#application'],
                ];
                $graph[] = [
                    '@type' => 'WebApplication',
                    '@id' => $this->url().'#application',
                    'url' => $this->url(),
                    'name' => $name,
                    'description' => $description,
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Web',
                    'inLanguage' => config()->string('metadata.language'),
                    'image' => $this->url(config()->string('metadata.social_image')),
                ];
            }
        }

        return [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'public' => $public,
            'robots' => $this->robots($request),
            'image' => $this->url(config()->string('metadata.social_image')),
            'jsonLd' => $graph === [] ? null : json_encode(
                ['@context' => 'https://schema.org', '@graph' => $graph],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
            ),
        ];
    }
}
