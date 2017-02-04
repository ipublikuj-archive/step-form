<?php
/**
 * IStorage.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:StepForm!
 * @subpackage     Storage
 * @since          1.0.0
 *
 * @date           04.02.17
 */

declare(strict_types = 1);

namespace IPub\StepForm\Storage;

use Nette;
use Nette\Http;

/**
 * Step form storage interface
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     Storage
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class StorageFactory
{
	/**
	 * @var ISession
	 */
	private $sessionFactory;

	/**
	 * @param ISession $sessionFactory
	 */
	public function __construct(ISession $sessionFactory)
	{
		$this->sessionFactory = $sessionFactory;
	}

	/**
	 * @param string $section
	 *
	 * @return IStorage
	 */
	public function create(string $section) : IStorage
	{
		return $this->sessionFactory->create($section);
	}
}
