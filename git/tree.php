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
use Gitsy\Repo;
use Gitsy\Git_Blob;

/**
 * Git tree class - describes
 * a tree in the Git Data
 * API
 *
 * @package  Gitsy
 * @author   Ben Corlett
 * @link     http://developer.github.com/v3/git/trees/
 */
class Git_Tree extends Entity
{
	/**
	 * Creates a tree on the GitHub API
	 * 
	 * With Auth:
	 * 
	 *   POST /repos/:user/:repo/git/trees
	 * 
	 * @access  public
	 * @param   string          $user     User login
	 * @param   string          $repo     Repo name
	 * @param   array           $objects  Tree arrat
	 * @param   mixed           $auth     Auth to use
	 * @return  Gitsy\Git_tree  $tree     New tree object
	 * @link    http://developer.github.com/v3/git/trees/#create-a-tree
	 */
	public static function create($user, $repo, array $tree, $auth = false)
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
		$result = Gitsy::post('/repos/'.$user.'/'.$repo.'/git/trees', $tree, $auth);

		// Create a new tree object
		$tree = static::forge($result, $auth);
		return $tree;
	}
}