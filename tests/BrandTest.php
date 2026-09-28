<?php

namespace FlyWP\Tests;

use FlyWP\Brand;
use PHPUnit\Framework\TestCase;

/**
 * The brand a reseller gives their customers' sites. `FLYWP_BRAND` is a contract with the FlyWP
 * control plane, which lives in another repository, so this pins one fixed vector that the
 * control plane's own suite asserts byte for byte. Regenerating it to make the test pass means
 * changing the format, and the control plane has to change with it.
 */
class BrandTest extends TestCase {

    const VECTOR = '7b226e616d65223a2241636d652773205c22486f7374696e675c22202620436f222c2275726c223a2268747470733a2f2f61636d652e666c7977702e78797a222c2269636f6e223a2268747470733a2f2f63646e2e61636d652e746573742f66617669636f6e2e706e67227d';

    public function test_it_reads_the_control_plane_vector() {
        $brand = Brand::decode( self::VECTOR );

        $this->assertTrue( $brand->is_set() );
        $this->assertSame( 'Acme\'s "Hosting" & Co', $brand->name() );
        $this->assertSame( 'https://acme.flywp.xyz', $brand->url() );
        $this->assertSame( 'https://cdn.acme.test/favicon.png', $brand->icon() );
        $this->assertSame( 'https://acme.flywp.xyz/site/42', $brand->dashboard_url( 42 ) );
    }

    /**
     * Without a brand the plugin shows exactly what it showed before brands existed.
     *
     * @dataProvider no_brand
     */
    public function test_anything_that_is_not_a_brand_is_flywp( $encoded ) {
        $brand = Brand::decode( $encoded );

        $this->assertFalse( $brand->is_set() );
        $this->assertSame( 'FlyWP', $brand->name() );
        $this->assertSame( 'https://flywp.com', $brand->url() );
        $this->assertSame( '', $brand->icon() );
        $this->assertSame( 'https://app.flywp.com/site/7', $brand->dashboard_url( 7 ) );
        $this->assertSame( 'https://app.flywp.com', $brand->dashboard_url() );
        // As before brands: a site info with id 0 still names it.
        $this->assertSame( 'https://app.flywp.com/site/0', $brand->dashboard_url( 0 ) );
    }

    public function no_brand() {
        return [
            'empty'           => [ '' ],
            'not a string'    => [ false ],
            'not hex'         => [ 'zz' ],
            'odd length'      => [ 'abc' ],
            'not JSON'        => [ bin2hex( 'Acme' ) ],
            'no name'         => [ $this->encode( [ 'url' => 'https://acme.test' ] ) ],
            'a blank name'    => [ $this->encode( [ 'name' => " \n ", 'url' => 'https://acme.test' ] ) ],
            'a name as array' => [ $this->encode( [ 'name' => [ 'Acme' ], 'url' => 'https://acme.test' ] ) ],
            'no URL'          => [ $this->encode( [ 'name' => 'Acme' ] ) ],
            'an http URL'     => [ $this->encode( [ 'name' => 'Acme', 'url' => 'http://acme.test' ] ) ],
            'a script URL'    => [ $this->encode( [ 'name' => 'Acme', 'url' => 'javascript:alert(1)' ] ) ],
            'a quote in URL'  => [ $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test/"onmouseover' ] ) ],
            'no host'         => [ $this->encode( [ 'name' => 'Acme', 'url' => 'https:///' ] ) ],
            'a newline after' => [ $this->encode( [ 'name' => 'Acme', 'url' => "https://acme.test\n" ] ) ],
            'a query'         => [ $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test/?a=1' ] ) ],
            'a fragment'      => [ $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test/#top' ] ) ],
            'uppercase hex'   => [ strtoupper( bin2hex( '{}' ) ) ],
        ];
    }

    public function test_it_keeps_only_an_https_icon_that_cannot_leave_an_attribute() {
        $icon = function ( $url ) {
            return Brand::decode( $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test', 'icon' => $url ] ) )->icon();
        };

        $this->assertSame( 'https://acme.test/icon.png', $icon( 'https://acme.test/icon.png' ) );
        $this->assertSame( '', $icon( 'http://acme.test/icon.png' ) );
        $this->assertSame( '', $icon( 'https://acme.test/a).png' ) );
        $this->assertSame( '', $icon( 'https://acme.test/a b.png' ) );
        $this->assertSame( '', $icon( "https://acme.test/icon.png\n" ) );
        // An icon may carry a query: a CDN's version stamp.
        $this->assertSame( 'https://cdn.acme.test/icon.png?v=2', $icon( 'https://cdn.acme.test/icon.png?v=2' ) );
        $this->assertSame( '', $icon( null ) );
        // A brand with no icon is still the brand, with no icon.
        $this->assertTrue( Brand::decode( $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test', 'icon' => null ] ) )->is_set() );
    }

    public function test_a_name_is_text_with_no_tags() {
        $brand = Brand::decode( $this->encode( [ 'name' => '<script>alert(1)</script>Acme <b>Cloud</b> & "Co"', 'url' => 'https://acme.test' ] ) );

        $this->assertSame( 'alert(1)Acme Cloud & "Co"', $brand->name() );
        // Nothing left but tags is no name: no brand.
        $this->assertFalse( Brand::decode( $this->encode( [ 'name' => '<img src=x onerror=alert(1)>', 'url' => 'https://acme.test' ] ) )->is_set() );
    }

    public function test_a_name_is_one_line_and_a_url_has_no_trailing_slash() {
        $brand = Brand::decode( $this->encode( [ 'name' => " Acme\r\nHosting\t", 'url' => 'https://acme.test/' ] ) );

        $this->assertSame( 'Acme Hosting', $brand->name() );
        $this->assertSame( 'https://acme.test', $brand->url() );
    }

    public function test_it_renames_the_plugin_row_under_a_brand_and_leaves_it_alone_without_one() {
        $row = [
            'Name'        => 'FlyWP',
            'Title'       => 'FlyWP',
            'Author'      => 'FlyWP',
            'AuthorName'  => 'FlyWP',
            'AuthorURI'   => 'https://flywp.com/?utm_source=wporg',
            'PluginURI'   => 'https://flywp.com',
            'Description' => 'Helper plugin for FlyWP',
            'Version'     => '1.7.1',
            'TextDomain'  => 'flywp',
        ];

        $this->assertSame( $row, Brand::decode( '' )->plugin_row( $row, 'unused' ) );

        $branded = Brand::decode( self::VECTOR )->plugin_row( $row, 'Tools from Acme.' );

        $this->assertSame( 'Acme\'s "Hosting" & Co', $branded['Name'] );
        $this->assertSame( 'Acme\'s "Hosting" & Co', $branded['Title'] );
        $this->assertSame( 'Acme\'s "Hosting" & Co', $branded['Author'] );
        $this->assertSame( 'Acme\'s "Hosting" & Co', $branded['AuthorName'] );
        $this->assertSame( 'https://acme.flywp.xyz', $branded['AuthorURI'] );
        $this->assertSame( 'https://acme.flywp.xyz', $branded['PluginURI'] );
        $this->assertSame( 'Tools from Acme.', $branded['Description'] );
        // What identifies the plugin to WordPress stays.
        $this->assertSame( '1.7.1', $branded['Version'] );
        $this->assertSame( 'flywp', $branded['TextDomain'] );
    }

    public function test_the_test_email_template_shows_flywp_without_a_brand_and_the_brand_with_one() {
        $template = file_get_contents( dirname( __DIR__ ) . '/views/email-template.html' );

        $flywp = Brand::decode( '' )->email_html( $template );

        // Byte for byte the template this plugin sent before brands existed.
        $this->assertSame( file_get_contents( __DIR__ . '/fixtures/email-template-1.7.1.html' ), $flywp );

        $this->assertStringContainsString( Brand::DEFAULT_EMAIL_LOGO, $flywp );
        $this->assertStringContainsString( 'href="https://flywp.com"', $flywp );
        $this->assertStringContainsString( 'FlyWP, Inc.', $flywp );
        $this->assertStringContainsString( 'Dover, DE 19901', $flywp );
        $this->assertStringNotContainsString( '{{brand_', $flywp );

        $branded = Brand::decode( self::VECTOR )->email_html( $template );

        $this->assertStringNotContainsString( '{{brand_', $branded );
        $this->assertStringNotContainsString( 'FlyWP', $branded );
        $this->assertStringNotContainsString( 'flywp.com', $branded );
        $this->assertStringContainsString( '<img src="https://cdn.acme.test/favicon.png" alt="Acme&#039;s &quot;Hosting&quot; &amp; Co"', $branded );
        $this->assertStringContainsString( '<strong>Acme&#039;s &quot;Hosting&quot; &amp; Co</strong>', $branded );
        $this->assertStringContainsString( 'href="https://acme.flywp.xyz"', $branded );

        // FlyWP's address goes with its name.
        $this->assertStringNotContainsString( 'Dover', $branded );

        // No icon: the name stands in for the logo, and no image is loaded from anywhere.
        $plain = Brand::decode( $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test' ] ) )->email_html( $template );
        $this->assertStringNotContainsString( '<img src="https://', $plain );
        $this->assertStringContainsString( 'email-masthead_name"', $plain );
    }

    public function test_it_renames_this_plugins_site_health_entry() {
        $info = [
            'wp-plugins-active' => [
                'fields' => [
                    'Akismet' => [ 'label' => 'Akismet', 'value' => 'Version 5.1 by Automattic' ],
                    'FlyWP'   => [ 'label' => 'FlyWP', 'value' => 'Version 1.7.1 by FlyWP', 'debug' => 'version: 1.7.1, author: FlyWP' ],
                    'Zephyr'  => [ 'label' => 'Zephyr', 'value' => 'Version 1.0 by Z' ],
                ],
            ],
        ];

        $this->assertSame( $info, Brand::decode( '' )->site_health( $info ) );

        $fields = Brand::decode( $this->encode( [ 'name' => 'Acme', 'url' => 'https://acme.test' ] ) )->site_health( $info )['wp-plugins-active']['fields'];

        $this->assertArrayNotHasKey( 'FlyWP', $fields );
        $this->assertSame( [ 'label' => 'Acme', 'value' => 'Version 1.7.1 by Acme', 'debug' => 'version: 1.7.1, author: Acme' ], $fields['Acme'] );
        $this->assertSame( $info['wp-plugins-active']['fields']['Akismet'], $fields['Akismet'] );
        // In its place in the list.
        $this->assertSame( [ 'Akismet', 'Acme', 'Zephyr' ], array_keys( $fields ) );
    }

    private function encode( array $brand ) {
        return bin2hex( json_encode( $brand ) );
    }
}
