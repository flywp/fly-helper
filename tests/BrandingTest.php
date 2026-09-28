<?php

namespace FlyWP\Tests;

use FlyWP\Brand;
use FlyWP\Branding;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/stubs/wordpress.php';

/**
 * The hooks that put a reseller's brand on the screens and emails WordPress builds from this
 * plugin's header. Without a brand, none is added.
 */
class BrandingTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['flywp_test_filters'] = [];
        $this->brand( [ 'name' => 'Acme Cloud', 'url' => 'https://acme.flywp.xyz' ] );
    }

    public function test_it_adds_no_hook_without_a_brand() {
        $GLOBALS['flywp_test_plugin'] = $this->plugin( Brand::decode( '' ) );

        new Branding();

        $this->assertSame( [], $GLOBALS['flywp_test_filters'] );
    }

    public function test_it_adds_its_four_hooks_with_a_brand() {
        new Branding();

        $this->assertSame(
            [ 'all_plugins' => 1, 'plugin_row_meta' => 2, 'auto_plugin_theme_update_email' => 4, 'debug_information' => 1 ],
            $GLOBALS['flywp_test_filters']
        );
    }

    public function test_it_renames_this_plugins_row_and_no_other() {
        $plugins = ( new Branding() )->all_plugins(
            [
                'flywp/flywp.php'     => [ 'Name' => 'FlyWP', 'Author' => 'FlyWP', 'Version' => '1.7.1' ],
                'akismet/akismet.php' => [ 'Name' => 'Akismet', 'Author' => 'Automattic', 'Version' => '5.1' ],
            ]
        );

        $this->assertSame( 'Acme Cloud', $plugins['flywp/flywp.php']['Name'] );
        $this->assertSame( 'Caching, email and performance tools from Acme Cloud.', $plugins['flywp/flywp.php']['Description'] );
        $this->assertSame( [ 'Name' => 'Akismet', 'Author' => 'Automattic', 'Version' => '5.1' ], $plugins['akismet/akismet.php'] );
    }

    public function test_it_drops_view_details_on_this_plugins_row_only() {
        $meta = [ 'Version 1.7.1', 'By Acme Cloud', '<a href="plugin-install.php?tab=plugin-information&#038;plugin=flywp">View details</a>' ];

        $this->assertSame( [ 'Version 1.7.1', 'By Acme Cloud' ], ( new Branding() )->plugin_row_meta( $meta, 'flywp/flywp.php' ) );
        $this->assertSame( $meta, ( new Branding() )->plugin_row_meta( $meta, 'akismet/akismet.php' ) );
    }

    public function test_it_rebuilds_this_plugins_line_of_core_update_email_and_leaves_the_others() {
        $item = function ( $file, $name, $from, $url ) {
            return (object) [ 'name' => $name, 'item' => (object) [ 'plugin' => $file, 'current_version' => $from, 'new_version' => '1.7.2', 'url' => $url ] ];
        };
        $body = implode(
            "\n",
            [
                'These plugins are now up to date:',
                '- FlyWP (from version 1.7.1 to 1.7.2) : https://wordpress.org/plugins/flywp/',
                '- FlyWP Migrator (from version 1.7.1 to 1.7.2) : https://wordpress.org/plugins/flywp-migrator/',
                'The following plugins failed to update.',
                '- FlyWP version 1.7.2 : https://wordpress.org/plugins/flywp/',
            ]
        );

        $email = ( new Branding() )->update_email(
            [ 'body' => $body ],
            'mixed',
            [ 'plugin' => [ $item( 'flywp/flywp.php', 'FlyWP', '1.7.1', 'https://wordpress.org/plugins/flywp/' ), $item( 'flywp-migrator/flywp-migrator.php', 'FlyWP Migrator', '1.7.1', 'https://wordpress.org/plugins/flywp-migrator/' ) ] ],
            [ 'plugin' => [ $item( 'flywp/flywp.php', 'FlyWP', '', 'https://wordpress.org/plugins/flywp/' ) ] ]
        );

        $this->assertSame(
            implode(
                "\n",
                [
                    'These plugins are now up to date:',
                    '- Acme Cloud (from version 1.7.1 to 1.7.2)',
                    '- FlyWP Migrator (from version 1.7.1 to 1.7.2) : https://wordpress.org/plugins/flywp-migrator/',
                    'The following plugins failed to update.',
                    '- Acme Cloud version 1.7.2',
                ]
            ),
            $email['body']
        );
    }

    public function test_a_name_with_regex_or_format_characters_is_written_as_it_is() {
        $this->brand( [ 'name' => 'Acme $1 \\ 100% Cloud', 'url' => 'https://acme.test' ] );
        $item  = (object) [ 'name' => 'FlyWP', 'item' => (object) [ 'plugin' => 'flywp/flywp.php', 'current_version' => '1.7.1', 'new_version' => '1.7.2', 'url' => '' ] ];

        $email = ( new Branding() )->update_email( [ 'body' => '- FlyWP (from version 1.7.1 to 1.7.2)' ], 'success', [ 'plugin' => [ $item ] ], [] );

        $this->assertSame( '- Acme $1 \\ 100% Cloud (from version 1.7.1 to 1.7.2)', $email['body'] );
    }

    private function brand( array $brand ) {
        $GLOBALS['flywp_test_plugin'] = $this->plugin( Brand::decode( bin2hex( json_encode( $brand ) ) ) );
    }

    private function plugin( Brand $brand ) {
        return new class( $brand ) {
            private $brand;

            public function __construct( $brand ) {
                $this->brand = $brand;
            }

            public function brand() {
                return $this->brand;
            }
        };
    }
}
