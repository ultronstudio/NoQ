<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://noq.example',
            'metadata.name' => 'Test NoQ',
            'metadata.description' => 'Testovací popis front.',
            'metadata.indexable' => true,
            'metadata.pwa_enabled' => true,
        ]);
    }

    public function test_public_metadata_uses_configured_origin_without_tracking_parameters(): void
    {
        $response = $this->get('https://another-host.example/?utm_source=test');

        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'index, follow, max-image-preview:large')
            ->assertSee('<title>Virtuální čekací fronty | Test NoQ</title>', false)
            ->assertSee('<meta name="description" content="Testovací popis front.">', false)
            ->assertSee('<link rel="canonical" href="https://noq.example/">', false)
            ->assertSee('content="https://noq.example/assets/branding/social.png"', false)
            ->assertSee('rel="manifest"', false);

        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $json = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(['WebPage', 'WebSite', 'WebApplication'], array_column($json['@graph'], '@type'));
        $this->assertSame('https://noq.example/', $json['@graph'][0]['url']);
        $this->assertSame('Test NoQ', $json['@graph'][1]['name']);
        $this->assertSame('Testovací popis front.', $json['@graph'][2]['description']);
    }

    public function test_private_pages_and_auth_redirects_are_not_indexable(): void
    {
        $this->get('/login')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertDontSee('application/ld+json', false)
            ->assertDontSee('rel="canonical"', false);

        $this->get('/dashboard')->assertRedirect()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertDontSee('application/ld+json', false);
    }

    public function test_robots_and_sitemap_use_only_configured_public_urls(): void
    {
        config(['metadata.sitemap.home' => [
            'lastmod' => '2026-10-05',
            'changefreq' => 'monthly',
            'priority' => 1.0,
        ]]);

        $this->get('/robots.txt')->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee("Allow: /\n\nSitemap: https://noq.example/sitemap.xml", false);

        $response = $this->get('/sitemap.xml')->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertDontSee('/login', false)
            ->assertDontSee('/dashboard', false);

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertCount(1, $xml->url);
        $this->assertSame('https://noq.example/', (string) $xml->url->loc);
        $this->assertSame('2026-10-05', (string) $xml->url->lastmod);
        $this->assertSame('monthly', (string) $xml->url->changefreq);
        $this->assertSame('1.0', (string) $xml->url->priority);

        $this->travel(7)->days();
        $this->get('/sitemap.xml')->assertOk()->assertContent($response->getContent());
    }

    public function test_optional_sitemap_fields_do_not_publish_private_pages_or_hide_zero_priority(): void
    {
        config(['metadata.sitemap' => [
            'home' => ['lastmod' => null, 'changefreq' => '', 'priority' => 0],
            'login' => ['lastmod' => '2026-10-05', 'priority' => 1.0],
        ]]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>https://noq.example/</loc>', false)
            ->assertSee('<priority>0.0</priority>', false)
            ->assertDontSee('<lastmod>', false)
            ->assertDontSee('<changefreq>', false)
            ->assertDontSee('/login', false);

        config(['metadata.sitemap' => []]);

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee('<loc>https://noq.example/</loc>', false)
            ->assertDontSee('<lastmod>', false)
            ->assertDontSee('<changefreq>', false)
            ->assertDontSee('<priority>', false);
    }

    public function test_explicit_settings_disable_indexing_and_pwa_links(): void
    {
        config(['metadata.indexable' => false, 'metadata.pwa_enabled' => false]);

        $this->get('/')->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertDontSee('rel="manifest"', false)
            ->assertDontSee('/pwa-register.js', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /', false);
        $this->get('/sitemap.xml')->assertOk()->assertDontSee('<loc>', false);
    }

    public function test_manifest_uses_config_and_references_real_sized_icons(): void
    {
        $response = $this->get('/manifest.webmanifest')->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'Test NoQ')
            ->assertJsonPath('description', 'Testovací popis front.')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('start_url', '/')
            ->assertJsonPath('scope', '/');

        foreach ($response->json('icons') as $icon) {
            $size = getimagesize(public_path($icon['src']));
            $this->assertNotFalse($size);
            $this->assertSame($icon['sizes'], $size[0].'x'.$size[1]);
            $this->assertSame('image/png', $size['mime']);
        }
    }

    public function test_json_ld_and_html_escape_configured_text(): void
    {
        $name = '</script><script>alert("name")</script>';
        config(['metadata.name' => $name, 'metadata.description' => 'Text " onmouseover="alert(1)']);

        $response = $this->get('/')->assertOk()->assertDontSee($name, false);
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $response->getContent(), $matches);
        $json = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($name, $json['@graph'][1]['name']);
        $response->assertSee('Text &quot; onmouseover=&quot;alert(1)', false);
    }
}
