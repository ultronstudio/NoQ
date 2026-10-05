<?php

namespace App\Http\Controllers;

use App\Support\Metadata;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MetadataController extends Controller
{
    public function robots(Metadata $metadata): Response
    {
        $content = "User-agent: *\n";
        $content .= config()->boolean('metadata.indexable')
            ? "Allow: /\n\nSitemap: ".$metadata->url('sitemap.xml')."\n"
            : "Disallow: /\n";

        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(Metadata $metadata): Response
    {
        $urls = [];

        if (config()->boolean('metadata.indexable')) {
            $settings = config()->array('metadata.sitemap');

            foreach (config()->array('metadata.public_routes') as $name) {
                $options = Validator::make($settings[$name] ?? [], [
                    'lastmod' => ['nullable', 'date_format:Y-m-d'],
                    'changefreq' => ['nullable', Rule::in(['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'])],
                    'priority' => ['nullable', 'numeric', 'between:0,1'],
                ])->validate();

                $urls[] = ['loc' => $metadata->url(route($name, absolute: false))] + $options;
            }
        }

        return response()->view('metadata.sitemap', compact('urls'), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    public function manifest(): JsonResponse
    {
        return response()->json([
            'id' => '/',
            'name' => config()->string('metadata.name'),
            'short_name' => config()->string('metadata.name'),
            'description' => config()->string('metadata.description'),
            'lang' => config()->string('metadata.language'),
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'theme_color' => config()->string('metadata.theme_color'),
            'background_color' => config()->string('metadata.background_color'),
            'icons' => [
                ['src' => '/assets/branding/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/branding/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/branding/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
