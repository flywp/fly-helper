<?php

namespace FlyWP\StaticSite\Engine\Discovery;

/**
 * Gives a group of URLs, for example all posts or all terms.
 *
 * @since 1.8.0
 */
interface UrlSource {

    /**
     * @return iterable<DiscoveredUrl>
     */
    public function urls();
}
