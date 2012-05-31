<?php
/**
 * Part of the Gitsy package for FuelPHP.
 *
 * @package    Gitsy
 * @version    1.0
 * @author     Cartalyst LLC
 * @license    MIT License
 * @copyright  2012 Cartalyst LLC
 * @link       http://cartalyst.com
 */

namespace Gitsy;

use ArrayAccess;
use Gitsy\GitsyAuthException;

/**
 * Entity class - used
 * to base an entity that
 * comes from the GitHub
 * API in a class.
 *
 * @package  Gitsy
 * @author   Ben Corlett
 */
abstract class Entity implements ArrayAccess
{
	/**
	 * Array of data from the GitHub
	 * API
	 * 
	 * @var array
	 */
	protected $data = array();

	/**
	 * The auth used when initialising this
	 * object. Will be passed to all calls
	 * for children of this object unless
	 * overridden in method calls
	 * 
	 * @var array
	 */
	protected $auth = false;

	/**
	 * Called when the class is constructed
	 * 
	 * @access  public
	 * @param   array   $data   Data from GitHub API
	 *                          used to initialise class
	 * @param   mixed   $auth   Auth to use
	 * @return  void
	 */
	public function __construct(array $data = array(), $auth = false)
	{
		// Set data in array
		foreach ($data as $property => $value)
		{
			$this->set($property, $value);
		}

		// Set auth
		$auth and $this->auth = $auth;

		// Post construct
		$this->_construct();
	}

	/**
	 * Sets a property in the data array
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @param   string  $value    Value
	 * @return  void
	 */
	public function set($property, $value)
	{
		array_set($this->data, $property, $value);
	}

	/**
	 * Gets a property fronm the data array
	 * 
	 * Note, default is parsed through
	 * Fuel::value() (by the Arr class)
	 * so it can be a set value or a Closure
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @param   mixed   $default  Default
	 * @return  mixed             Value
	 */
	public function get($property, $default = null)
	{
		return array_get($this->data, $property, $default);
	}

	/**
	 * Determines if a property in the
	 * data array is set or not
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Is set or not
	 */
	public function is_set($property)
	{
		return (bool) $this->get($property, false);
	}

	/**
	 * Unsets a property from the data
	 * array
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Success
	 */
	public function uns($property)
	{
		return array_forget($this->data, $property);
	}

	/**
	 * Gets all data from the data
	 * array
	 * 
	 * @access  public
	 * @return  array  Data array
	 */
	public function data()
	{
		return $this->data;
	}

	/**
	 * Forces auth to be present
	 * on an entity subclass.
	 * 
	 * Throws an exception if auth
	 * is not present
	 * 
	 * @access  protected
	 * @param   string   $caller   Caller method
	 *                             (usually __METHOD__)
	 * @throws  Gitsy\GitsyAuthException
	 * @return  void
	 */
	protected function force_auth($caller = false)
	{
		if ($this->auth === false)
		{
			throw new GitsyAuthException('Auth is required'.($caller ? ' for '.$caller : null));
		}
	}

	/* ---------------------------------------------------------------------------
	 * Magic methods
	 * --------------------------------------------------------------------------- */

	/**
	 * Sets a property in the data array
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @param   string  $value    Value
	 * @return  void
	 */
	public function __set($property, $value = null)
	{
		return $this->set($property, $value);
	}

	/**
	 * Gets a property fronm the data array
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @return  mixed             Value
	 */
	public function __get($property)
	{
		return $this->get($property);
	}

	/**
	 * Determines if a property in the
	 * data array is set or not
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Is set or not
	 */
	public function __isset($property)
	{
		return $this->is_set($property);
	}

	/**
	 * Unsets a property from the data
	 * array
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Success
	 */
	public function __unset($property)
	{
		return $this->uns($property);
	}

	/* ---------------------------------------------------------------------------
	 * ArrayAccess implementation
	 * --------------------------------------------------------------------------- */

	/**
	 * Sets a property in the data array
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @param   string  $value    Value
	 * @return  void
	 */
	public function offsetSet($property, $value)
	{
		return $this->set($property, $value);
	}

	/**
	 * Gets a property fronm the data array
	 * 
	 * @access  public
	 * @param   string  $property Property
	 * @return  mixed             Value
	 */
	public function offsetGet($property)
	{
		return $this->get($property);
	}

	/**
	 * Determines if a property in the
	 * data array is set or not
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Is set or not
	 */
	public function offsetExists($property)
	{
		return $this->is_set($property);
	}

	/**
	 * Unsets a property from the data
	 * array
	 * 
	 * @access  public
	 * @param   string  $property  Property
	 * @return  bool               Success
	 */
	public function offsetUnset($property)
	{
		$this->uns($property);
	}
}