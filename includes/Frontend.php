<?php

namespace FlyWP;

class Frontend {

    /**
     * Plugin Constructor.
     *
     * @return void
     */
    public function __construct() {
        new Frontend\MagicLogin();
    }
}
