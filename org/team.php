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

use Gitsy\Repo;

/**
 * Organisation Team class - describes
 * a GitHub Organisation Team
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/teams/
 */
class Org_Team extends Entity
{
	/**
	 * Gets the repos for this team
	 * 
	 * With Auth:
	 * 
	 *   GET /teams/:id/repos
	 * 
	 * @access  public
	 * @return  array  $repos  Array of Gitsy\Repo objects
	 * @link    http://developer.github.com/v3/orgs/teams/#list-team-repos
	 */
	public function repos()
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::get('/teams/'.$this['id'].'/repos', array(), $this->auth);

		$repos = array();

		foreach ($result as $repo)
		{
			array_set($repos, array_get($repo, 'name'), Repo::forge($repo, $this->auth));
		}

		return $repos;
	}
}