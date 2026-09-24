<?php

namespace FlyWP\StaticSite\Engine\Incremental;

/**
 * Writes, reads and clears change rows.
 *
 * @since 1.8.0
 */
interface ChangeStore {

    /**
     * @param string      $kind        url, delete, list or global.
     * @param string|null $object_type post, term, user, blog, archive, date or null.
     * @param int|null    $object_id   Object ID.
     * @param string|null $url         Absolute URL.
     * @param string      $reason      Short reason.
     *
     * @return bool False on a database error.
     */
    public function insert( $kind, $object_type, $object_id, $url, $reason );

    /**
     * Rows with $after_id < id <= $up_to_id, in ID order.
     *
     * @param int $after_id Last ID of the previous batch.
     * @param int $up_to_id Highest ID to read.
     * @param int $limit    Batch size.
     *
     * @return array<int, array{id:int, kind:string, object_type:string|null, object_id:int|null, url:string|null, reason:string}>
     */
    public function rows( $after_id, $up_to_id, $limit );

    /**
     * @param int $after_id Lowest ID, not included.
     * @param int $up_to_id Highest ID, included.
     *
     * @return bool True when a global row is in the range.
     */
    public function has_global( $after_id, $up_to_id );

    /**
     * @return int Highest row ID, 0 when there are no rows.
     */
    public function max_id();

    /**
     * @param int $change_id Highest row ID to delete.
     *
     * @return bool False on a database error.
     */
    public function clear_up_to( $change_id );
}
