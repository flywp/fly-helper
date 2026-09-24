<?php
/**
 * A fake Redirection plugin item for RedirectSourceUrlsTest.
 */

if ( ! class_exists( 'Red_Item' ) ) {
    // phpcs:ignore Generic.Files.OneObjectStructurePerFile.MultipleFound
    class Red_Item {

        /**
         * @var Red_Item[]
         */
        public static $items = [];

        /**
         * @var string
         */
        private $url;

        /**
         * @var bool
         */
        private $regex;

        public function __construct( $url, $regex = false ) {
            $this->url   = $url;
            $this->regex = $regex;
        }

        public static function get_all() {
            return self::$items;
        }

        public function is_enabled() {
            return true;
        }

        public function is_dynamic() {
            return false;
        }

        public function is_regex() {
            return $this->regex;
        }

        public function get_match_type() {
            return 'url';
        }

        public function get_action_type() {
            return 'url';
        }

        public function get_url() {
            return $this->url;
        }
    }
}
