<?php

namespace FlyWP\Tests\StaticSite\Engine\Discovery;

use FlyWP\StaticSite\Engine\Discovery\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase {

    /**
     * @dataProvider resolve_cases
     */
    public function test_resolve( $base, $reference, $expected ) {
        $this->assertSame( $expected, Url::resolve( $base, $reference ) );
    }

    public function resolve_cases() {
        $base = 'http://a.test/b/c/d;p?q';

        return [
            'absolute'      => [ $base, 'https://x.test/y', 'https://x.test/y' ],
            'network path'  => [ $base, '//x.test/y', 'http://x.test/y' ],
            'absolute path' => [ $base, '/g', 'http://a.test/g' ],
            'relative'      => [ $base, 'g', 'http://a.test/b/c/g' ],
            'dot'           => [ $base, './g/', 'http://a.test/b/c/g/' ],
            'dot dot'       => [ $base, '../../g', 'http://a.test/g' ],
            'too many dots' => [ $base, '../../../../g', 'http://a.test/g' ],
            'query only'    => [ $base, '?y', 'http://a.test/b/c/d;p?y' ],
            'empty'         => [ $base, '', $base ],
            'root base'     => [ 'http://a.test', 'about/', 'http://a.test/about/' ],
        ];
    }

    public function test_build_drops_the_default_port() {
        $this->assertSame( 'https://a.test/x?y=1', Url::build( Url::parts( 'https://a.test:443/x?y=1' ) ) );
        $this->assertSame( 'http://a.test:8080/', Url::build( Url::parts( 'http://a.test:8080/' ) ) );
        $this->assertTrue( Url::is_http( 'https://a.test/' ) );
        $this->assertFalse( Url::is_http( '/relative' ) );
    }
}
