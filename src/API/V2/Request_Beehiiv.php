<?php
namespace Re_Beehiiv\API\V2;

defined( 'ABSPATH' ) || exit;


class Request_Beehiiv {

	public static function get(string $api_key = null, string $endpoint, $should_validate = true)
    {

        $headers = self::get_headers($api_key);
        $response = \Requests::get( Routes::get_base_url() . $endpoint, $headers );

		return $response;

	}

	public static function post(string $api_key = null, string $endpoint, string $data)
    {

		$headers = self::get_headers($api_key);
        $response = \Requests::post( Routes::get_base_url() . $endpoint, $headers, $data );

		return $response;

	}

	public static function patch(string $api_key = null, string $endpoint, string $data)
    {

		$headers = self::get_headers($api_key);
		$url = Routes::get_base_url() . $endpoint;

		return \Requests::patch( $url,  $headers,  $data );

    }

	public static function delete(string $api_key = null, string $endpoint)
    {

        $headers = self::get_headers($api_key);

        $url = Routes::get_base_url() . $endpoint;
		return \Requests::delete( $url,  $headers);

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