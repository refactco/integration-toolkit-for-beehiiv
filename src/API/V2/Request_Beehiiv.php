<?php
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;


class Request_Beehiiv {

    /**
     * Send a GET request to the Beehiiv API
     *
     * @param string|null $api_key
     * @param string $endpoint
     * @return array|/WP_Error
     */
	public static function get(string $api_key = null, string $endpoint)
    {

        $headers = self::get_headers($api_key);
        $response = wp_remote_get( Routes::get_base_url() . $endpoint, array(
            'headers' => $headers,
            'timeout' => 60,
        ));

        return $response;

	}

    private static function get_headers(?string $api_key): array
    {

		if ($api_key == null) {
			return [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			];
		}

        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        );

        return $headers;

    }

}