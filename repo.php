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
use Gitsy\Git_Blob;
use Gitsy\Git_Commit;
use Gitsy\Git_Ref;
use Gitsy\Git_Tree;
use Gitsy\Org_Team;
use Gitsy\Pull;
use Gitsy\User;

/**
 * Repo class - describes
 * a GitHub Repo
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/repos/
 * @todo     Separate the git methods from normal methods.
 *           For example, commits() should lead to the
 *           /repos/:user/:repo/commits, and
 *           git_commits() should lead to
 *           /repos/:user/:repo/git/commits.
 */
class Repo extends Entity
{
	/**
	 * The user object this
	 * repo belongs to
	 */
	public $user;

	/**
	 * Returns the login for the
	 * owner of this repo
	 * 
	 * @access  public
	 * @return  string   $login  Login
	 */
	public function login()
	{
		$login = $this->user ? $this->user['login'] : $this['owner']['login'];
		return $login;
	}

	/* ---------------------------------------------------------------------------
	 * Repo methods
	 * --------------------------------------------------------------------------- */

	/**
	 * Updates this repo on GitHub
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo
	 * 
	 * @access  public
	 * @param   array   $data  Repo data
	 * @return  Gitsy\Repo
	 * @link    http://developer.github.com/v3/repos/#edit
	 */
	public function update(array $data)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/repos/'.$this->login().'/'.$this['name'], $data, $this->auth);

		foreach ($result as $property => $value)
		{
			$this->set($property, $value);
		}

		return $this;
	}

	/**
	 * Lists all contributors for a
	 * repo
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/contributors
	 * 
	 * @access  public
	 * @return  array  $contributors  Contributors
	 * @link    http://developer.github.com/v3/repos/#list-contributors
	 */
	public function contributors()
	{
		$result       = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/contributors', array(), false);
		$contributors = array();

		foreach ($result as $user)
		{
			$contributors[array_get($user, 'login', false)] = User::forge($user, $this->auth);
		}

		return $contributors;
	}

	/**
	 * Lists languages that a repo has
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/languages
	 * 
	 * @access  public
	 * @return  array  Languages
	 * @link    http://developer.github.com/v3/repos/#list-languages
	 */
	public function languages()
	{
		return Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/languages', array(), $this->auth);
	}

	/**
	 * Lists teams that work on a particular
	 * repo
	 * 
	 * With Auth:
	 * 
	 *   GET /repos/:user/:repo/teams
	 * 
	 * @access  public
	 */
	public function teams()
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/teams', array(), $this->auth);

		$teams = array();

		foreach ($result as $team)
		{
			$teams[array_get($team, 'id', false)] = Org_Team::forge($team, $this->auth);
		}

		return $teams;
	}

	/* ---------------------------------------------------------------------------
	 * Git Data: Refs
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets all ref objects for a
	 * repo
	 *
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/refs
	 * 
	 * @access  public
	 * @return  array   $refs   Array of refs objects
	 * @link    http://developer.github.com/v3/git/refs/#get-all-references
	 */
	public function git_refs()
	{
		// Get refs array
		$result = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/git/refs', array(), $this->auth);

		// Refs array
		$refs = array();

		foreach ($result as $ref)
		{
			// Create a ref class
			$ref_c = Git_Ref::forge($ref, $this->auth);
			$ref_c->repo = $this;

			// Ref is the key, e.g. refs/heads/master
			$refs[array_get($ref, 'ref', false)] = $ref_c;
		}

		return $refs;
	}

	/**
	 * Gets a ref by the given key
	 * 
	 * $master_ref = $repo->ref('refs/heads/master');
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/git/refs/:ref
	 * 
	 * @access  public
	 * @param   string    $key  Ref key
	 * @return  Gitsy\Ref $ref  Ref object
	 * @link    http://developer.github.com/v3/git/refs/#get-a-reference
	 */
	public function git_ref($key)
	{
		$result = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/git/'.$key, array(), $this->auth);
		$ref    = Git_Ref::forge($result, $this->auth);
		$ref->repo = $this;

		return $ref;
	}

	/**
	 * Creates a ref on this repo
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/refs
	 * 
	 * @access  public
	 * @param   array         $data  Ref data
	 * @return  Gitsy\Git_Ref $ref   New ref object
	 * @link    http://developer.github.com/v3/git/refs/#create-a-reference
	 */
	public function create_git_ref(array $data)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/repos'.$this->login().'/'.$this['name'].'/git/refs', $data, $this->auth);
		$ref    = Git_Ref::forge($result, $this->auth);

		return $ref;
	}

	/* ---------------------------------------------------------------------------
	 * Git Data: Commits
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets a commit from git by the
	 * given SHA
	 * 
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/git/commits/:sha
	 * 
	 * @access  public
	 * @param   string           $sha    Commit SHA
	 * @return  Gitsy\Git_Commit $commit Commit object
	 * @link    http://developer.github.com/v3/git/commits/#get-a-commit
	 */
	public function git_commit($sha)
	{
		$result = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/git/commits/'.$sha, array(), $this->auth);
		$commit = Git_Commit::forge($result, $this->auth);

		return $commit;
	}

	/**
	 * Creates a commit on the GitHub API
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/commits
	 * 
	 * @access  public
	 * @param   array             $data    Commit data
	 * @return  Gitsy\Git_Commit  $commit  New commit object
	 * @link    http://developer.github.com/v3/git/commits/#create-a-commit
	 */
	public function create_git_commit(array $data)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/repos/'.$this->login().'/'.$this['name'].'/git/commits', $data, $this->auth);

		$commit = Git_Commit::forge($result, $this->auth);
		return $commit;
	}

	/* ---------------------------------------------------------------------------
	 * Git Data: Blobs
	 * --------------------------------------------------------------------------- */

	/**
	 * Creates a blob on the GitHub API
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/blobs
	 * 
	 * @access  public
	 * @param   array  $blob    Blob data
	 * @return  Gitsy\Git_Blog  Blob object
	 * @link    http://developer.github.com/v3/git/blobs/#create-a-blob
	 */
	public function create_git_blob(array $blob)
	{
		$this->force_auth(__METHOD__);

		$result = Gitsy::post('/repos/'.$this->login().'/'.$this['name'].'/git/blobs', $blob, $this->auth);

		return Git_Blob::forge($result);
	}

	/* ---------------------------------------------------------------------------
	 * Git Data: Trees
	 * --------------------------------------------------------------------------- */

	/**
	 * Creates a git tree on the GitHub API
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/trees
	 * 
	 * @access  public
	 * @param   array           $objects  Tree array
	 * @return  Gitsy\Git_tree  $tree     New tree object
	 * @link    http://developer.github.com/v3/git/trees/#create-a-tree
	 */
	public function create_git_tree(array $tree)
	{
		$this->force_auth(__METHOD__);

		/**
		 * Housekeeping and formatting of
		 * the objects within the tree
		 * 
		 * @todo  Look at relevance of this
		 */
		foreach ($tree['tree'] as &$object)
		{
			/**
			 * Object contains the content
			 * of a new blob to be created
			 */
			if ($blob = array_get($object, 'blob') and $blob instanceof Git_Blob)
			{
				// Passing a blob object, get it's SHA and format appropriately
			}

			/**
			 * Object contains the SHA
			 * of an object (tree or blob)
			 */ 
			elseif (array_get($object, 'sha'))
			{
				// Might do something?
			}

			/**
			 * Else, we've been given the
			 * string contents of a blob object
			 * that GitHub should create when we
			 * POST to it's API
			 */
			else
			{
				// Might do something?
			}
		}

		// Post to the API
		$result = Gitsy::post('/repos/'.$this->login().'/'.$this['name'].'/git/trees', $tree, $this->auth);

		// Create a new tere object
		$tree = Git_Tree::forge($result, $this->auth);
		return $tree;
	}

	/* ---------------------------------------------------------------------------
	 * Pull Requests
	 * --------------------------------------------------------------------------- */

	/**
	 * Gets the pull requests for the repo
	 *
	 * With / Without Auth:
	 * 
	 *   GET /repos/:user/:repo/pulls
	 * 
	 * @access  public
	 * @return  array   $pulls  Array of pulls
	 * @link    http://developer.github.com/v3/pulls/#list-pull-requests
	 */
	public function pulls()
	{
		$result = Gitsy::get('/repos/'.$this->login().'/'.$this['name'].'/pulls', array(), $this->auth);

		$pulls = array();

		foreach ($result as $pull)
		{
			$pull_c = Pull::forge($pull);
			$pull_c->repo = $this;
			$pulls[] = $pull;
		}

		return $pulls;
	}

	/**
	 * Creates a pull request in the GitHub API
	 * and returns a Gitsy\Pull object that encapsulates
	 * that request.
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/pulls
	 * 
	 * @access  public
	 * @param   string           $title  Pull request title
	 * @param   string           $body   Pull request body
	 * @param   string|Gitsy\Ref $head   Branch or git ref where changes are
	 *                                   implemented (the repo requesting the pull)
	 * @param   string|Gitsy\Ref $base   Teh branch or git ref we want to pull
	 *                                   our changes into
	 * @return  Gitsy\Pull
	 * @link    http://developer.github.com/v3/pulls/#create-a-pull-request
	 */
	public function create_pull($title, $body, $head = 'master', $base = 'master')
	{
		$this->force_auth(__METHOD__);

		if ($head instanceof Git_Ref)
		{
			// Convert to string, e.g. refs/heads/master
			$head = $head['ref'];
		}

		if ($base instanceof Git_Ref)
		{
			$base = $base['ref'];
		}

		/**
		 * Namespace our head property as per
		 * GitHub docs
		 */
		if (strpos($head, $this->login()) !== 0)
		{
			$head = $this->login().':'.$head;
		}

		// Build up the parent key (where we will
		// post this pull request to)
		$key = $this['parent']['owner']['login'].'/'.$this['parent']['name'];

		$result = Gitsy::post('/repos/'.$key.'/pulls', array(
			'title' => $title,
			'body'  => $body,
			'head'  => $head,
			'base'  => $base,
		), $this->auth);

		$repo = Pull::forge($result, $this->auth);
		return $repo;
	}
}