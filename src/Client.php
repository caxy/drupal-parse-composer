<?php

namespace Drupal\ParseComposer;

use GuzzleHttp\Client as BaseClient;

/**
 * Modified guzzle client to parse xml.
 */
class Client extends BaseClient
{
    /**
     * {@inheritdoc}
     */
    public function get($uri, array $options = [])
    {
        $response = parent::get($uri, $options);

        return response_to_xml($response);
    }
}
