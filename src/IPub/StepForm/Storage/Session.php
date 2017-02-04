<?php
/**
 * SessionStorage.php
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
 * Step form session storage
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     Storage
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Session implements IStorage
{
	/**
	 * @var Http\SessionSection
	 */
	private $session;

	/**
	 * @var \DateTime|string|int
	 */
	private $expiration = '+ 20 minutes';

	/**
	 * @param string $sectionName
	 * @param Http\Session $session
	 */
	public function __construct(string $sectionName, Http\Session $session)
	{
		$this->session = $session->getSection('ipub.step-form-' . $sectionName);
		$this->session->setExpiration($this->expiration);
	}

	/**
	 * {@inheritdoc}
	 */
	public function set(string $key, $value)
	{
		$this->session->$key = $value;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get(string $key, $default = FALSE)
	{
		return isset($this->session->$key) ? $this->session->$key : $default;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear(string $key)
	{
		unset($this->session->$key);
	}

	/**
	 * {@inheritdoc}
	 */
	public function clearAll()
	{
		$this->session->remove();
	}
}
