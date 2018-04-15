<?php

namespace FormTools;


/**
 * The interface for the transport layer.
 * Interface RequestTransport
 * @package FormTools
 */
interface RequestsTransportInterface
{

    /**
     * Handles making a request to a particular URL.
     * @param $url
     * @return mixed
     */
    public static function request ($url);

}
