<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * One URL that the engine must fetch.
 *
 * @since 1.8.0
 */
class DiscoveredUrl {

    /**
     * Absolute URL.
     *
     * @var string
     */
    public $url;

    /**
     * Kind: home, post, page, cpt, term, author, archive, pagination, feed, sitemap, asset or file.
     *
     * @var string
     */
    public $kind;

    /**
     * Object type: post, term, user or null.
     *
     * @var string|null
     */
    public $object_type;

    /**
     * Object ID.
     *
     * @var int|null
     */
    public $object_id;

    /**
     * Last change time, Y-m-d H:i:s GMT. Null when it is not known.
     *
     * @var string|null
     */
    public $modified_gmt;

    /**
     * @param string      $url          Absolute URL.
     * @param string      $kind         URL kind.
     * @param string|null $object_type  Object type.
     * @param int|null    $object_id    Object ID.
     * @param string|null $modified_gmt Last change time in GMT.
     */
    public function __construct( $url, $kind, $object_type = null, $object_id = null, $modified_gmt = null ) {
        $this->url          = $url;
        $this->kind         = $kind;
        $this->object_type  = $object_type;
        $this->object_id    = null === $object_id ? null : (int) $object_id;
        $this->modified_gmt = $modified_gmt ? $modified_gmt : null;
    }
}
