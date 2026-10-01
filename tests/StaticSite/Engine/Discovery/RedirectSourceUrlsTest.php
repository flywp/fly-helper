<?php

namespace FlyWP\Tests\StaticSite\Engine\Discovery;

use FlyWP\StaticSite\Engine\Discovery\RedirectSourceUrls;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/fixtures/red-item.php';

class RedirectSourceUrlsTest extends TestCase {

    protected function tearDown(): void {
        \Red_Item::$items = [];
    }

    public function test_redirection_sources_move_to_the_origin_host() {
        \Red_Item::$items = [
            new \Red_Item( 'https://www.example.com/old-page' ),
            new \Red_Item( 'http://example.com/other-page/?a=1' ),
            new \Red_Item( '/relative/' ),
            new \Red_Item( 'https://cdn.example.org/file' ),
            new \Red_Item( '/wild*' ),
            new \Red_Item( '^/regex/(.*)$', true ),
            new \Red_Item( 'https://site.example.net/from-site-url/' ),
        ];

        $urls = RedirectSourceUrls::all( 'http://origin.test:8080', [ 'https://example.com/', 'https://site.example.net' ] );

        $this->assertSame(
            [
                'http://origin.test:8080/old-page',
                'http://origin.test:8080/other-page/?a=1',
                'http://origin.test:8080/relative/',
                'http://origin.test:8080/from-site-url/',
            ],
            $urls
        );
    }

    public function test_to_origin() {
        $this->assertSame( 'https://example.com/a/', RedirectSourceUrls::to_origin( 'http://www.example.com/a/', 'https://example.com', [] ) );
        $this->assertNull( RedirectSourceUrls::to_origin( 'https://evil.test/a/', 'https://example.com', [] ) );
        $this->assertNull( RedirectSourceUrls::to_origin( '  ', 'https://example.com', [] ) );
        $this->assertSame( 'https://a.test/blog/old-page', RedirectSourceUrls::to_origin( '/blog/old-page', 'https://a.test/blog', [] ) );
        $this->assertSame( 'https://a.test/blog/old-page', RedirectSourceUrls::to_origin( 'old-page', 'https://a.test/blog', [] ) );
    }
}
