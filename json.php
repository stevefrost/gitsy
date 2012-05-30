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

class JSONException extends Exception
{
	const JSON_ERROR_DEPTH          = 9001;
	const JSON_ERROR_STATE_MISMATCH = 9002;
	const JSON_ERROR_CTRL_CHAR      = 9003;
	const JSON_ERROR_SYNTAX         = 9004;
	const JSON_ERROR_UTF8           = 9005;
	const JSON_ERROR_UNKNOWN        = 9006;
}

class JSON
{

	/**
	 * A wrapper for the json_encode function.
	 *
	 * @param   mixed  The value to encode
	 * @param   int    The options to use
	 * @return  string
	 */
	public static function encode($object, $options = 0)
	{
		return json_encode($object, $options);
	}

	/**
	 * A wrapper for the json_decode function which turns any JSON error
	 * into a JSONException.
	 *
	 * @param   string  The JSON to decode
	 * @param   bool    Whether to make it an assoc array or not
	 * @param   int     The max depth to decode to.
	 * @return  mixed
	 * @throws  JSONException
	 */
	public static function decode($json, $assoc = false, $depth = 512)
	{
		$return = json_decode($json, $assoc, $depth);

		switch (json_last_error())
		{
			case JSON_ERROR_NONE:
				return $return;
			break;
			case JSON_ERROR_DEPTH:
				throw new JSONException('Maximum stack depth exceeded', JSONException::JSON_ERROR_DEPTH);
			break;
			case JSON_ERROR_STATE_MISMATCH:
				throw new JSONException('Underflow or the modes mismatch', JSONException::JSON_ERROR_STATE_MISMATCH);
			break;
			case JSON_ERROR_CTRL_CHAR:
				throw new JSONException('Unexpected control character found', JSONException::JSON_ERROR_CTRL_CHAR);
			break;
			case JSON_ERROR_SYNTAX:
				throw new JSONException('Syntax error, malformed JSON', JSONException::JSON_ERROR_SYNTAX);
			break;
			case JSON_ERROR_UTF8:
				throw new JSONException('Malformed UTF-8 characters, possibly incorrectly encoded', JSONException::JSON_ERROR_UTF8);
			break;
			default:
				throw new JSONException('Unknown error', JSONException::JSON_ERROR_UNKNOWN);
			break;
		}
	}
}
