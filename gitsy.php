<?php
/**
 * Part of the Gitsy bundle for Laravel.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Gitsy
 * @version    1.0
 * @author     Cartalyst LLC
 * @license    BSD License (3-clause)
 * @copyright  (c) 2011 - 2012, Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Gitsy;

use Exception;
use Gitsy\Org;
use Gitsy\User;
use Str;

class GitsyAuthException     extends Exception {}
class GitsyRequestException  extends Exception {}
class GitsyNotFoundException extends Exception {}

/**
 * @todo implement paging
 *       functionality
 */

/**
 * Gitsy class
 *
 * @package  Gitsy
 * @author   Ben Corlett
 */
class Gitsy
{

	/**
	 * GitHub API URI
	 *
	 * @var string
	 */
	protected static $github_api = 'https://api.github.com';

	/*
	|--------------------------------------------------------------------------
	| API helpers
	|--------------------------------------------------------------------------
	*/

	/**
	 * API Helper: HEAD
	 *
	 * Can be issued against any resource to get just the HTTP header info.
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array
	 */
	public static function head($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('HEAD', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper: GET
	 *
	 * Used for retrieving resources.
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array
	 */
	public static function get($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('GET', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper: POST
	 *
	 * Used for creating resources, or performing custom
	 * actions (such as merging a pull request).
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array
	 */
	public static function post($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('POST', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper: PATCH
	 *
	 * Used for updating resources with partial JSON data.
	 * For instance, an Issue resource has title and body
	 * attributes. A PATCH request may accept one or more
	 * of the attributes to update the resource. PATCH is
	 * a relatively new and uncommon HTTP verb, so resource
	 * endpoints also accept POST requests.
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array    Data
	 */
	public static function patch($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('PATCH', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper: PUT
	 *
	 * Used for replacing resources or collections.
	 * For PUT requests with no body attribute, be sure
	 * to set the Content-Length header to zero.
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array    Data
	 */
	public static function put($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('PUT', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper: DELETE
	 *
	 * Used for deleting resources.
	 *
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @return  array
	 */
	public static function delete($resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		return static::api('DELETE', $resource, $parameters, $auth, $additional_options);
	}

	/**
	 * API Helper
	 *
	 * @param   string   $http_method
	 * @param   string   $resource
	 * @param   array    $parameters
	 * @param   mixed    $auth
	 * @param   array    $additional_options
	 * @throws  Gitsy\GitsyRequestException
	 * @return  array
	 */
	public static function api($http_method, $resource, array $parameters = array(), $auth = false, array $additional_options = array())
	{
		// Sanitise HTTP method
		$http_method = Str::upper($http_method);

		// Encode parameters
		$parameters = JSON::encode($parameters);

		// Default connection headers
		$headers = array(
			'Content-Type: text/json',
		);

		// Default cURL options
		$options = array(
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_CUSTOMREQUEST  => $http_method,
		);

		// Process http method. Notice we're skipping
		// GET as it doesn't modify any options or headers
		// from default.
		switch ($http_method)
		{
			case 'HEAD':
				$options[CURLOPT_POSTFIELDS] = $parameters;
				$headers[]                   = 'X-HTTP-Method-Override: HEAD';
				break;

			case 'POST':
				$options[CURLOPT_POST]       = true;
				$options[CURLOPT_POSTFIELDS] = $parameters;
				break;

			case 'PATCH':
				$options[CURLOPT_POSTFIELDS] = $parameters;
				$headers[]                   = 'X-HTTP-Method-Override: PATCH';
				break;

			case 'PUT':
				$options[CURLOPT_POSTFIELDS] = $parameters;
				$headers[]                   = 'X-HTTP-Method-Override: PUT';
				break;

			case 'DELETE':
				$options[CURLOPT_POSTFIELDS] = $parameters;
				$headers[]                   = 'X-HTTP-Method-Override: DELETE';
				break;
		}

		// Process Authentication
		if (is_string($auth))
		{
			$headers[] = 'Authorization: token '.$auth;
		}
		elseif (is_array($auth))
		{
			$options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
			$options[CURLOPT_USERPWD]  = $auth[0].':'.$auth[1];
		}

		// Merge in headers now before any additional options
		// are passed
		$options[CURLOPT_HTTPHEADER] = $headers;

		// Add additional options
		foreach ($additional_options as $key => $value)
		{
			// Modify the key
			if (is_string($key) and ! is_numeric($key))
			{
				if ( ! starts_with($key, 'CURLOPT_'))
				{
					$key = 'CURLOPT_'.$key;
				}

				$key = constant('CURLOPT_'.Str::upper($key));
			}

			$options[$key] = $value;
		}

		// Create a cURL connection
		$ch = curl_init(static::$github_api.$resource);
		curl_setopt_array($ch, $options);

		// Look at what GitHub sends back
		$body      = curl_exec($ch);
		$info      = curl_getinfo($ch);
		$http_code = $info['http_code'];

		// If the HTTP code is in 400-500, throw an exception. We might have
		// got a message back from the API, if so, put that in the exception
		// as well.
		if ($http_code >= 400)
		{
			$message = "HTTP Error [$http_code]";

			try
			{
				$body     = JSON::decode($body, true);
				$message .= ' '.$body['message'];
			}
			catch (\Exception $e)
			{
				$message .= ' Unkown Error';
			}

			$message .= " ($http_method $resource)";

			if (is_string($auth))
			{
				$message .= " (Auth - $auth)";
			}
			elseif (is_array($auth))
			{
				$message .= " (Auth - {$auth[0]}:{$auth[1]})";
			}

			$message .= " (Params - $parameters)";
			
			/**
			 * Format an exception in the below format:
			 *
			 * [cURL Error Message] [GitHub Error] ([HTTP Method] [Resource] [Auth] [Parameters])
			 *
			 * For example:
			 *
			 * The requested URL returned error: 401
			 * (GET /sdf foo@bar.com:password123 {"param1":"value1"})
			 */
			throw new GitsyRequestException($message, $http_code);
		}

		return JSON::decode($body, true);
	}

	/*
	|--------------------------------------------------------------------------
	| Static usage
	|--------------------------------------------------------------------------
	*/

	/**
	 * Gets a user from the API
	 *
	 * With Auth:
	 *
	 *   GET /user
	 *
	 * Without Auth:
	 *
	 *   GET /users/:user
	 *
	 * @param   string     $username
	 * @param   mixed      $auth
	 * @return  Gitsy\User $user
	 * @link    http://developer.github.com/v3/users/#get-a-single-user
	 * @link    http://developer.github.com/v3/users/#get-the-authenticated-user
	 */
	public static function user($username = null, $auth = false)
	{
		$result = $auth === false ? static::get('/users/'.$username) : static::get('/user', array(), $auth);
		
		return new User($result, $auth);
	}

	/**
	 * Gets an organisation from the API
	 *
	 * With / Without Auth:
	 *
	 *   GET /orgs/:org
	 *
	 * @param   string    $org
	 * @param   mixed     $auth
	 * @return  Gitsy\Org $org_c
	 * @link    http://developer.github.com/v3/orgs/#get
	 */
	public static function org($org = null, $auth = false)
	{
		$result = static::get('/orgs/'.$org);

		return new Org($result, $auth);
	}

	/**
	 * Shortcut for Gitsy::user('username')->repo('reponame');
	 *
	 * Note: You cannot use this method to access a repo
	 *       owned by a team that belongs to an organisation.
	 *       You need to go through Gitsy::org('orgname')
	 *                                   ->team(123)
	 *                                   ->repo('reponame');
	 *
	 * Usage:
	 *
	 * Gitsy::repo('username/reponame');
	 *
	 * @param   string     $key
	 * @param   mixed      $auth
	 * @return  Gitsy\Repo $repo
	 */
	public static function repo($key, $auth = false)
	{
		$parts = explode('/', $key);
		$user  = static::user($parts[0], $auth);
		$repo  = $user->repo($parts[1]);
		return $repo;
	}

}
