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

use Gitsy\Repo_Entity;

/**
 * Git ref class - describes
 * a ref in the Git Data
 * API
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/git/refs/
 */
class Git_Ref extends Repo_Entity
{
	/**
	 * Updates a ref object
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/refs/:ref
	 * 
	 * @access  public
	 * @param   array   $data  Data to update ref by
	 * @return  Gitsy\Git_Ref
	 * @link    http://developer.github.com/v3/git/refs/#update-a-reference
	 */
	public function update(array $data)
	{
		$this->force_auth(__METHOD__);
		
		// Get result
		$result = Gitsy::post('/repos/'.$this->login().'/'.$this->repo['name'].'/git/'.$this['ref'], $data, $this->auth);

		// Reset the data
		$this->data = array();

		// Update our data
		foreach ($result as $property => $value)
		{
			$this->set($property, $value);
		}

		return $this;
	}
}