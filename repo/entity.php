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
 * Repo Entity class - used
 * to base an entity that belongs
 * to a repo
 *
 * @package  Gitsy
 * @author   Ben Corlett
 */
abstract class Repo_Entity extends Entity
{
	/**
	 * The repo this entity belongs
	 * to
	 */
	public $repo;

	/**
	 * Returns the login for the
	 * owner of the repo that owns
	 * this entity
	 */
	public function login()
	{
		return $this->repo->login();
	}
}