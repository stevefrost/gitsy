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

use Gitsy\Entity;
use Gitsy\GitsyRequestException;
use Gitsy\Org;
use Gitsy\Repo;

/**
 * User class - describes
 * a GitHub user
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/users/
 */
class User extends Entity
{
	/* ---------------------------------------------------------------------------
	 * User methods
	 * --------------------------------------------------------------------------- */

	/**
	 * Updates the user information
	 * 
	 * With Auth:
	 * 
	 *   POST /user
	 * 
	 * @access  public
	 * @param   array   $properties  User properties
	 * @return  Gitsy\User
	 * @link    http://developer.github.com/v3/users/#update-the-authenticated-user
	 */
	public function update(array $properties)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/user', $properties, $this->auth);

		/**
		 * Loop through result and update
		 * class properties
		 */
		foreach ($result as $property => $value)
		{
			$this->set($property, $value);
		}

		return $this;
	}

	/* ---------------------------------------------------------------------------
	 * Emails
	 * --------------------------------------------------------------------------- */

	/**
	 * Returns an array of the user's emails
	 * 
	 * With Auth:
	 * 
	 *   GET /user/emails
	 * 
	 * @access  public
	 * @return  array   User emails
	 * @link    http://developer.github.com/v3/users/emails/#list-email-addresses-for-a-user
	 */
	public function emails()
	{
		$this->force_auth(__METHOD__);
		return Gitsy::get('/user/emails', array(), $this->auth);
	}

	/**
	 * Adds email addresses to the user
	 * 
	 * With Auth:
	 * 
	 *   POST /user/emails
	 * 
	 * @access  public
	 * @param   string|array  $emails  Single email or array of emails
	 * @return  array                  The user's new set of emails
	 * @link    http://developer.github.com/v3/users/emails/#add-email-addresses
	 */
	public function add_emails($emails)
	{
		$this->force_auth(__METHOD__);

		if (is_string($emails))
		{
			$emails = array($emails);
		}

		return Gitsy::post('/user/emails', $emails, $this->auth);
	}

	/**
	 * Delets email addresses from the
	 * user
	 * 
	 * With Auth:
	 * 
	 *   DELETE /user/emails
	 * 
	 * @access  public
	 * @param   string|array  $emails  Single email or array of emails
	 * @return  array                  The user's new set of emails
	 * @link    http://developer.github.com/v3/users/emails/#delete-email-addresses
	 */
	public function delete_emails($emails)
	{
		$this->force_auth(__METHOD__);

		if (is_string($emails))
		{
			$emails = array($emails);
		}

		return Gitsy::delete('/user/emails', $emails, $this->auth);
	}

	/* ---------------------------------------------------------------------------
	 * Followers
	 * --------------------------------------------------------------------------- */

	/**
	 * Lists the user's followers
	 * 
	 * With Auth:
	 * 
	 *   GET /user/followers
	 * 
	 * Without Auth:
	 * 
	 *   GET /users/:user/followers
	 * 
	 * @access  public
	 * @return  array  $followers  An array of followers (User objects)
	 * @link    http://developer.github.com/v3/users/followers/#list-followers-of-a-user
	 */
	public function followers()
	{
		$result = Gitsy::get($this->auth ? '/user/followers' : '/users/'.$this->login().'/followers', array(), $this->auth);

		$followers = array();

		foreach ($result as $follower)
		{
			$followers[array_get($follower, 'login', false)] = new static($follower, $this->auth);
		}

		return $followers;
	}

	/**
	 * Lists the users that this
	 * user is following
	 * 
	 * With Auth:
	 * 
	 *   GET /user/following
	 * 
	 * Without Auth:
	 * 
	 *   GET /users/:user/following
	 * 
	 * @access  public
	 * @return  array  $following  An array of users this user is following
	 * @link    http://developer.github.com/v3/users/followers/#list-users-following-another-user
	 */
	public function following()
	{
		$result = Gitsy::get($this->auth ? '/user/following' : '/users/'.$this->login().'/following', array(), $this->auth);

		$following = array();

		foreach ($result as $user)
		{
			$following[array_get($user, 'login', false)] = new static($user, $this->auth);
		}

		return $following;
	}

	/**
	 * Check if this user is following another
	 * user
	 * 
	 * With Auth:
	 * 
	 *    GET /user/following/:user
	 * 
	 * @access  public
	 * @param   string  $user  User login
	 * @return  bool           Is following a user
	 * @link    http://developer.github.com/v3/users/followers/#check-if-you-are-following-a-user
	 */
	public function is_following($user)
	{
		$this->force_auth(__METHOD__);

		try
		{
			$result = Gitsy::get('/user/following/'.$user, array(), $this->auth);
		}
		catch (GitsyRequestException $e)
		{
			if ($e->getCode() !== 404)
			{
				throw $e;
			}

			return false;
		}

		// NULL result is HTTP 204 - no content (is following)
		return (bool) ($result === null);
	}

	/**
	 * Follow a user
	 * 
	 * With Auth:
	 * 
	 *   PUT /users/following/:user
	 * 
	 * @access  public
	 * @param   string   $user    User login
	 * @return  bool              Successful follow
	 * @link    http://developer.github.com/v3/users/followers/#follow-a-user
	 */
	public function follow($user)
	{
		$this->force_auth(__METHOD__);

		// No response is good - HTTP 204
		return (bool) (Gitsy::put('/user/following/'.$user, array(), $this->auth) === null);
	}

	/**
	 * Unfollow a user
	 * 
	 * With Auth:
	 * 
	 *   DELETE /users/following/:user
	 * 
	 * @access  public
	 * @param   string   $user  User login
	 * @return  bool            Successful unfollow
	 * @link    http://developer.github.com/v3/users/followers/#unfollow-a-user
	 */
	public function unfollow($user)
	{
		$this->force_auth(__METHOD__);

		// No response is good - HTTP 204
		return (bool) (Gitsy::delete('/user/following/'.$user, array(), $this->auth) === null);
	}

	/* ---------------------------------------------------------------------------
	 * Keys
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets the public keys for a user
	 * 
	 * With Auth:
	 * 
	 *   GET /user/keys
	 * 
	 * @access  public
	 * @return  array  $keys  User's keys
	 * @link    http://developer.github.com/v3/users/keys/#list-public-keys-for-a-user
	 */
	public function keys()
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::get('/user/keys', array(), $this->auth);

		$keys = array();

		/**
		 * Add keys with the ID
		 * as the array key
		 */
		foreach ($result as $key)
		{
			$keys[array_get($key, 'id', false)] = $key;
		}

		return $keys;
	}

	/**
	 * Gets a single key, given
	 * by the key ID
	 * 
	 * With Auth:
	 * 
	 *   GET /user/keys/:id
	 * 
	 * @access  public
	 * @param   int    $id  Key ID
	 * @return  array       Key
	 * @link    http://developer.github.com/v3/users/keys/#get-a-single-public-key
	 */
	public function key($id)
	{
		$this->force_auth(__METHOD__);

		return Gitsy::get('/user/keys/'.$id, array(), $this->auth);
	}

	/**
	 * Creates a key for the user
	 * 
	 * With Auth:
	 * 
	 *   POST /user/keys
	 * 
	 * If your key isn't valid you're going
	 * to get a HTTP 422 exception
	 * 
	 * @access  public
	 * @param   array   $key     Key information
	 * @return  array            New key returned
	 * @link    http://developer.github.com/v3/users/keys/#create-a-public-key
	 */
	public function create_key(array $key)
	{
		$this->force_auth(__METHOD__);

		return Gitsy::post('/user/keys', $key, $this->auth);
	}

	/**
	 * Updates a key for the user
	 * 
	 * With Auth:
	 * 
	 *   POST /user/keys/:id
	 * 
	 * @access  public
	 * @param   int    $id  Key ID
	 * @param   string $key Updated key information
	 * @return  array       Updated key
	 * @link    http://developer.github.com/v3/users/keys/#update-a-public-key
	 */
	public function update_key($id, array $key)
	{
		$this->force_auth(__METHOD__);

		return Gitsy::post('/users/keys/'.$id, $key, $this->auth);
	}

	/**
	 * Deletes a key for the user
	 * 
	 * With Auth:
	 * 
	 *   DELETE /user/keys/:id
	 * 
	 * @access  public
	 * @param   int   $id  Key ID
	 * @return  bool       Success
	 * @link     
	 */
	public function delete_key($id)
	{
		$this->force_auth(__METHOD__);

		return (bool) (Gitsy::delete('/user/keys/'.$id, array(), $this->auth) === null);
	}

	/* ---------------------------------------------------------------------------
	 * Repos
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets all repos for the user
	 * 
	 * With Auth:
	 * 
	 *   GET /user/repos
	 * 
	 * Without Auth:
	 * 
	 *   GET /users/:user/repos
	 * 
	 * @access  public
	 * @return  array  $repos Array of Gitsy\Repo objects
	 * @link    http://developer.github.com/v3/repos/#list-your-repositories
	 * @link    http://developer.github.com/v3/repos/#list-user-repositories
	 */
	public function repos()
	{
		// Get API result
		$result = $this->auth ? Gitsy::get('/user/repos', array(), $this->auth) : Gitsy::get('/users/'.$this['login'].'/repos');

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
			array_set($repos, array_get($repo, 'name'), new Repo($repo, $this->auth));
		}

		return $repos;
	}

	/**
	 * Gets a particular repo for this user
	 * 
	 * This method provides more info than
	 * Gitsy\User::repos() which just calls
	 * the method to list all of the repos
	 * for a user.
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /users/:user/:repo
	 * 
	 * @access  public
	 * @param   string      $name  Repo name
	 * @return  Gitsy\Repo  $repo  Fetched repo
	 * @link    http://developer.github.com/v3/repos/#get
	 */
	public function repo($name)
	{
		$repo = new Repo(Gitsy::get('/repos/'.$this['login'].'/'.$name, array(), $this->auth), $this->auth);
		$repo->user = $this;

		return $repo;
	}

	/**
	 * Creates a repo
	 * 
	 * With Auth:
	 * 
	 *   POST /user/repos
	 * 
	 * @access  public
	 * @param   array       $repo   Repo information
	 * @return  Gitsy\Repo  $repo_o New repo object
	 * @link    http://developer.github.com/v3/repos/#create
	 */
	public function create_repo(array $repo)
	{
		$this->force_auth(__METHOD__);
		$result = Gitsy::post('/user/repos', $repo, $this->auth);		

		$repo_o = new Repo($result, $this->auth);
		$repo_o->user = $this;

		return $repo_o;
	}

	/**
	 * Makes this user fork a repo (passed by
	 * either a username/reponame key combo or
	 * a Gitsy\Repo object)
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/forks
	 * 
	 * (Where :user / :repo refer to the user / repo
	 * we are forking, not the one doing the fork [the, forkee?])
	 * 
	 * @access  public
	 * @param   string|Gitsy\Repo $repo    Repo to fork
	 * @param   string|Gitsy\Org  $org     Organisation login to use
	 * @return  Gitsy\Repo        $forked  Forked repo
	 * @link    http://developer.github.com/v3/repos/forks/#create-a-fork
	 */
	public function fork($repo, $org = false)
	{
		// Get repo key
		if ($repo instanceof Repo)
		{
			// Convert Gitsy\Repo object to string
			$repo = $repo->login().'/'.$repo['name'];
		}

		// Process organisation
		$params = array();
		if ($org !== false)
		{
			if ($org instanceof Org)
			{
				$org = $org['login'];
			}

			array_set($params, 'org', $org);
		}

		$result = Gitsy::post('/repos/'.$repo.'/forks', $params, $this->auth);

		$repo = new Repo($result, $this->auth);
		$repo->user = $this;

		return $repo;
	}
}