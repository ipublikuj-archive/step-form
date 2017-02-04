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

namespace IPub\StepForm\Components;

/**
 * Step form control factory
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IControl
{
	/**
	 * @param string $formName
	 * @param string|NULL $templateFile
	 *
	 * @return Control
	 */
	public function create(string $formName, string $templateFile = NULL) : Control;
}
