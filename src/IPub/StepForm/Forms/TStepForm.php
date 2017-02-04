<?php
/**
 * Control.php
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

namespace IPub\StepForm\Forms;

use Nette;
use Nette\Utils;

use IPub;
use IPub\StepForm;
use IPub\StepForm\Components;

trait TStepForm
{
	/**
	 * @param int|NULL $step
	 * @param bool $asArray
	 *
	 * @return Utils\ArrayHash|NULL
	 */
	public function getStepValues(int $step = NULL, bool $asArray = FALSE)
	{
		return $this->getStepForm()->getValues($step, $asArray);
	}

	/**
	 * @return Components\Control
	 */
	private function getStepForm() : Components\Control
	{
		return $this->lookup(Components\Control::class);
	}
}
