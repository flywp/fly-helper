<?php

namespace FlyWP;

/**
 * The brand a FlyWP White Label reseller gives the sites of their customers.
 *
 * The control plane writes it to the site's config as one constant, `FLYWP_BRAND`: the hex of a
 * JSON object with a `name`, an https `url` and an optional https `icon`. The format is a contract
 * with the control plane, which lives in another repository, so `BrandTest` pins one fixed vector
 * that the control plane's own suite asserts too.
 *
 * No WordPress function is called here, so the rules are unit tested without WordPress. A value
 * that does not decode to a name and an https URL is no brand: the plugin then shows FlyWP, the
 * same as before brands existed.
 */
class Brand {

    const DEFAULT_NAME = 'FlyWP';

    const DEFAULT_URL = 'https://flywp.com';

    const DEFAULT_DASHBOARD_URL = 'https://app.flywp.com';

    /**
     * The wp.org page WordPress core links to in its plugin update email.
     */
    const WPORG_URL = 'https://wordpress.org/plugins/flywp/';

    /**
     * The footer of `views/email-template.html` without a brand: FlyWP's company and address.
     * Under a brand, the brand's name alone.
     */
    const DEFAULT_EMAIL_COMPANY = "FlyWP, Inc.\n                                            <br />8 The Green\n                                            <br />Dover, DE 19901";

    /**
     * The masthead of `views/email-template.html` without a brand.
     */
    const DEFAULT_EMAIL_LOGO = '<img src="https://app.flywp.com/assets/images/logo.svg" alt="FlyWP"' . "\n" . '                                    style="width: 150px; max-width: 100%; margin: 0; padding: 0; -premailer-width: 94px; -premailer-cellpadding: 0; -premailer-cellspacing: 0;">';

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $icon = '';

    /**
     * Reads a `FLYWP_BRAND` value.
     *
     * @param mixed $encoded
     *
     * @return self
     */
    public static function decode( $encoded ) {
        $brand = new self();

        if ( ! is_string( $encoded ) || $encoded === '' ) {
            return $brand;
        }

        // Hex, not base64: `base64_decode()` reads as obfuscation to a plugin reviewer.
        $json = ctype_xdigit( $encoded ) && strlen( $encoded ) % 2 === 0 ? hex2bin( $encoded ) : false;
        $data = $json === false ? null : json_decode( $json, true );

        if ( ! is_array( $data ) ) {
            return $brand;
        }

        // A name is one line of text: no control characters to break a header or an email line,
        // and no tags, whichever screen forgets to escape it. Each screen still escapes it.
        $name = isset( $data['name'] ) && is_string( $data['name'] ) ? trim( (string) preg_replace( '/[\x00-\x1F\x7F]+/u', ' ', strip_tags( $data['name'] ) ) ) : '';
        $url  = self::https( isset( $data['url'] ) ? $data['url'] : null );

        if ( $name === '' || $url === '' ) {
            return $brand;
        }

        $brand->name = $name;
        $brand->url  = rtrim( $url, '/' );
        $brand->icon = self::https( isset( $data['icon'] ) ? $data['icon'] : null );

        return $brand;
    }

    /**
     * Whether a reseller's brand is set. Without one, every method answers FlyWP.
     *
     * @return bool
     */
    public function is_set() {
        return $this->name !== '';
    }

    /**
     * @return string
     */
    public function name() {
        return $this->is_set() ? $this->name : self::DEFAULT_NAME;
    }

    /**
     * @return string
     */
    public function url() {
        return $this->is_set() ? $this->url : self::DEFAULT_URL;
    }

    /**
     * The brand's icon, or '' for none. Never FlyWP's logo under a brand.
     *
     * @return string
     */
    public function icon() {
        return $this->icon;
    }

    /**
     * Where the dashboard button goes: the site's page in the dashboard its owner signs in to.
     *
     * @param int|null $site_id
     *
     * @return string
     */
    public function dashboard_url( $site_id = null ) {
        $base = $this->is_set() ? $this->url : self::DEFAULT_DASHBOARD_URL;

        return $site_id ? $base . '/site/' . (int) $site_id : $base;
    }

    /**
     * This plugin's row in a list of plugins, under the brand. The same row without a brand.
     *
     * @param array  $row         the header fields WordPress read from `flywp.php`
     * @param string $description the plugin's description under the brand
     *
     * @return array
     */
    public function plugin_row( array $row, $description ) {
        if ( ! $this->is_set() ) {
            return $row;
        }

        return array_merge(
            $row,
            [
                'Name'        => $this->name,
                'Title'       => $this->name,
                'Author'      => $this->name,
                'AuthorName'  => $this->name,
                'AuthorURI'   => $this->url,
                'PluginURI'   => $this->url,
                'Description' => $description,
            ]
        );
    }

    /**
     * WordPress core's plugin update email names each plugin by its header, and links to its
     * wp.org page. On this plugin's lines, the brand takes the name and the link goes.
     *
     * @param string $body
     *
     * @return string
     */
    public function update_email_body( $body ) {
        if ( ! $this->is_set() ) {
            return $body;
        }

        $name = str_replace( [ '\\', '$' ], [ '\\\\', '\\$' ], $this->name );

        return (string) preg_replace(
            '/^- ' . preg_quote( self::DEFAULT_NAME, '/' ) . ' (.*?)(?: : ' . preg_quote( self::WPORG_URL, '/' ) . '?)?$/m',
            '- ' . $name . ' $1',
            $body
        );
    }

    /**
     * `views/email-template.html` with its `{{brand_*}}` places filled: FlyWP's text and logo
     * without a brand; the brand's name and icon, or its name for no icon, with one.
     *
     * @param string $template
     *
     * @return string
     */
    public function email_html( $template ) {
        if ( ! $this->is_set() ) {
            return strtr(
                $template,
                [
                    '{{brand_logo}}'    => self::DEFAULT_EMAIL_LOGO,
                    '{{brand_url}}'     => self::DEFAULT_URL,
                    '{{brand_name}}'    => self::DEFAULT_NAME,
                    '{{brand_company}}' => self::DEFAULT_EMAIL_COMPANY,
                ]
            );
        }

        $name = htmlspecialchars( $this->name, ENT_QUOTES, 'UTF-8' );
        $logo = $this->icon === '' ? $name : '<img src="' . htmlspecialchars( $this->icon, ENT_QUOTES, 'UTF-8' ) . '" alt="' . $name . '" style="width: 48px; max-width: 100%; margin: 0; padding: 0;">';

        return strtr(
            $template,
            [
                '{{brand_logo}}'    => $logo,
                '{{brand_url}}'     => htmlspecialchars( $this->url, ENT_QUOTES, 'UTF-8' ),
                '{{brand_name}}'    => $name,
                '{{brand_company}}' => $name,
            ]
        );
    }

    /**
     * Site Health lists active plugins by their header name. This plugin's entry, under the brand.
     *
     * @param array $info
     *
     * @return array
     */
    public function site_health( array $info ) {
        if ( ! $this->is_set() || ! isset( $info['wp-plugins-active']['fields'][ self::DEFAULT_NAME ] ) ) {
            return $info;
        }

        $field          = $info['wp-plugins-active']['fields'][ self::DEFAULT_NAME ];
        $field['label'] = $this->name;

        foreach ( [ 'value', 'debug' ] as $key ) {
            if ( isset( $field[ $key ] ) && is_string( $field[ $key ] ) ) {
                $field[ $key ] = str_replace( self::DEFAULT_NAME, $this->name, $field[ $key ] );
            }
        }

        unset( $info['wp-plugins-active']['fields'][ self::DEFAULT_NAME ] );
        $info['wp-plugins-active']['fields'][ $this->name ] = $field;

        return $info;
    }

    /**
     * An https URL with nothing that could leave an HTML attribute or a CSS `url()`, or ''.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function https( $value ) {
        return is_string( $value ) && preg_match( '#^https://[^\s"\'<>`()\\\\]+$#i', $value ) === 1 ? $value : '';
    }
}
