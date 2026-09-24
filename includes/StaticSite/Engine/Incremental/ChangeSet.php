<?php

namespace FlyWP\StaticSite\Engine\Incremental;

/**
 * The work of an incremental build.
 *
 * - full_reason = "global_change" or "large_list": the engine must run a full build.
 * - full_reason = null and urls and deleted_urls are empty: nothing changed.
 *
 * @since 1.8.0
 */
class ChangeSet {

    /**
     * Reason for a full build: "global_change", "large_list", "no_changes" or null.
     *
     * @var string|null
     */
    public $full_reason = null;

    /**
     * Absolute URLs to rebuild. All pages of each list in $lists are included.
     *
     * @var string[]
     */
    public $urls = [];

    /**
     * Absolute URLs that are gone.
     *
     * @var string[]
     */
    public $deleted_urls = [];

    /**
     * Each list page (home or posts page, post type archive, term, author, date) with its current page count.
     * The engine deletes the files of pages after the last page.
     *
     * @var array<int, array{url:string, pages:int}>
     */
    public $lists = [];

    /**
     * Highest change row ID in this set. The engine clears rows up to this ID after the build.
     *
     * @var int
     */
    public $max_change_id = 0;
}
