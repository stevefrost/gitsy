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

use Gitsy\Entity;

/**
 * Git commit class - describes
 * a commit in the Git Data
 * API
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/git/commits/
 */
class Git_Commit extends Entity
{
	/**
	 * Creates a commit on the GitHub API
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/commits
	 * 
	 * @access  public
	 * @param   string            $user    User login
	 * @param   string            $repo    Repo name
	 * @param   array             $data    Commit data
	 * @param   mixed             $auth    Auth to use
	 * @return  Gitsy\Git_Commit  $commit  New commit object
	 * @link    http://developer.github.com/v3/git/commits/#create-a-commit
	 */
	public static function create($user, $repo, array $data, $auth)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/repos/'.$user.'/'.$repo.'/git/commits', $data, $auth);

		$commit = static::forge($result, $auth);
		return $commit;
	}
}