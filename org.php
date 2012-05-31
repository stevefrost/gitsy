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

use Gitsy\GitsyRequestException;
use Gitsy\Org_Team;
use Gitsy\Repo;

/**
 * Org class - describes
 * a GitHub Organisation
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/orgs/
 */
class Org extends Entity
{
	/* ---------------------------------------------------------------------------
	 * Members
	 * --------------------------------------------------------------------------- */

	/**
	 * Returns an array of members for an organisation
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /orgs/:org/members
	 * 
	 * @access  public
	 * @return  array   $members  Members
	 * @link    http://developer.github.com/v3/orgs/members/#list-members
	 */
	public function members()
	{
		$members = Gitsy::get('/orgs/'.$this['login'].'/members', array(), $this->auth);
		return $members;
	}

	/**
	 * Returns if a username is a member
	 * of this organisation.
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /orgs/:org/members/:user
	 * 
	 * @access  public
	 * @return  bool     Is member
	 * @link    http://developer.github.com/v3/orgs/members/#get-member
	 */
	public function member($username)
	{
		try
		{
			$result = Gitsy::get('/orgs/'.$this['login'].'/members/'.$username, array(), $this->auth);
		}
		catch (GitsyRequestException $e)
		{
			if ($e->getCode() !== 404)
			{
				throw $e;
			}

			return false;
		}

		// NULL result is HTTP 204 - no content (is member)
		return (bool) ($result === null);
	}

	/* ---------------------------------------------------------------------------
	 * Teams
	 * --------------------------------------------------------------------------- */

	/**
	 * Returns an array of team objects
	 * 
	 * Note: Not all team info is returned
	 *       by this method. Instaed, use
	 *       Nesty\Org::team() and provide
	 *       the Team ID to that method
	 * 
	 * With Auth:
	 * 
	 *   GET /orgs/:org/teams
	 * 
	 * @access  public
	 * @return  array   $teams  Array of Gitsy\Team objects
	 * @link    http://developer.github.com/v3/orgs/teams/#list-teams
	 */
	public function teams()
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::get('/orgs/'.$this['login'].'/teams', array(), $this->auth);

		$teams = array();

		foreach ($result as $team)
		{
			$team_c = Org_Team::forge($team, $this->auth);
			array_set($teams, array_get($team, 'id'), $team_c);
		}

		return $teams;
	}

	/**
	 * Gets a specific team, given by the
	 * Team ID
	 * 
	 * With Auth:
	 * 
	 *   GET /teams/:id
	 * 
	 * @access  public
	 * @param   int         $id    Team ID
	 * @return  Gitsy\Team  $team  Team object
	 * @link    http://developer.github.com/v3/orgs/teams/#get-team
	 */
	public function team($id)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::get('/teams/'.$id, array(), $this->auth);
		$team   = Org_Team::forge($result, $this->auth);
		return $team;
	}

	/* ---------------------------------------------------------------------------
	 * Repo
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets all repos for the user
	 * 
	 * For some reason this only
	 * lists public repos, you need to
	 * get a team through Gitsy\Org::team()
	 * and list it's repos and providing valid
	 * auth for a member of that team.
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /orgs/:org/repos
	 * 
	 * @access  public
	 * @return  array  $repos Array of Gitsy\Repo objects
	 * @link    http://developer.github.com/v3/repos/#list-organization-repositories
	 */
	public function repos()
	{
		// Get API result
		$result = Gitsy::get('/orgs/'.$this['login'].'/repos');

		// Repos array
		$repos = array();

		/**
		 * Loop through repos and initiate
		 * object, and store the repos
		 * in the repo array using their
		 * name as the key (makes it easier
		 * to find repos in the array later)
		 */
		foreach ($result as $repo)
		{
			array_set($repos, array_get($repo, 'name'), Repo::forge($repo, $this->auth));
		}

		return $repos;
	}

	/**
	 * Gets a particular repo for this organisation
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /users/:user/:repo
	 * 
	 * @access  public
	 * @param   string      $name  Repo name
	 * @return  Gitsy\Repo         Fetched repo
	 * @link    http://developer.github.com/v3/repos/#get
	 */
	public function repo($name)
	{
		return Repo::forge(Gitsy::get('/repos/'.$this['login'].'/'.$name, array(), $this->auth));
	}
}