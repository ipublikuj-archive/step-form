<?php
/**
 * IControl.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:StepForm!
 * @subpackage     Components
 * @since          1.0.0
 *
 * @date           12.03.14
 */

declare(strict_types = 1);

namespace IPub\StepForm\Storage;

/**
 * Session storage factory
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface ISession
{
	/**
	 * @param string $sectionName
	 *
	 * @return Session
	 */
	public function create(string $sectionName) : Session;
}
