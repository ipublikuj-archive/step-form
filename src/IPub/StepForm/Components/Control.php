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

namespace IPub\StepForm\Components;

use Nette;
use Nette\Application;
use Nette\Bridges;
use Nette\Forms;
use Nette\Localization;
use Nette\Utils;

use IPub;
use IPub\StepForm\Exceptions;
use IPub\StepForm\Storage;

/**
 * Step form control container definition
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     Components
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 *
 * @property Application\UI\ITemplate $template
 *
 * @method onValidate(Control $control, Forms\Form $form)
 * @method onSubmit(Control $control, Forms\Form $form)
 * @method onSuccess(Control $control, Forms\Form $form)
 * @method onError(Control $control, Forms\Form $form)
 * @method onComplete(Control $control)
 */
class Control extends Application\UI\Control
{
	/**
	 * Step storage key
	 */
	const STEP_SESSION_KEY = 'values%d';

	/**
	 * Buttons controls names
	 */
	const BUTTON_PREV_NAME = 'prevStep';
	const BUTTON_NEXT_NAME = 'nextStep';
	const BUTTON_FINISH_NAME = 'finish';

	/**
	 * @persistent
	 * @var int
	 */
	public $step = 1;

	/**
	 * @var callable[]
	 */
	public $onValidate = [];

	/**
	 * @var callable[]
	 */
	public $onSubmit = [];

	/**
	 * @var callable[]
	 */
	public $onSuccess = [];

	/**
	 * @var callable[]
	 */
	public $onError = [];

	/**
	 * @var callable[]
	 */
	public $onComplete = [];

	/**
	 * @var string[]
	 */
	private $menu = [];

	/**
	 * @var callable[]
	 */
	private $formCallbacks = [];

	/**
	 * @var bool
	 */
	private $useLinkSteps = FALSE;

	/**
	 * @var bool
	 */
	private $fillWithDefaults = TRUE;

	/**
	 * @var string|NULL
	 */
	private $templateFile = NULL;

	/**
	 * @var Storage\IStorage
	 */
	private $storage;

	/**
	 * @var Localization\ITranslator
	 */
	private $translator;

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return void
	 */
	public function injectTranslator(Localization\ITranslator $translator = NULL)
	{
		$this->translator = $translator;
	}

	/**
	 * @param string $formName
	 * @param string|NULL $templateFile
	 * @param Storage\StorageFactory $storageFactory
	 */
	public function __construct(
		string $formName = 'step-form',
		string $templateFile = NULL,
		Storage\StorageFactory $storageFactory
	) {
		list(, , , $parent, $cname) = func_get_args() + [NULL, NULL, NULL, NULL, NULL];

		parent::__construct($parent, $cname);

		$this->storage = $storageFactory->create($formName);

		if ($templateFile) {
			$this->setTemplateFile($templateFile);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function attached($presenter)
	{
		parent::attached($presenter);

		foreach ($this->getComponents(FALSE, Forms\Form::class) as $form) {
			$this->attachButtonsCallbacks($form);
		}
	}

	/**
	 * @return void
	 */
	public function beforeRender()
	{
		// Check if control has template
		if ($this->template instanceof Bridges\ApplicationLatte\Template) {
			$this->template->add('steps', $this->getComponents(FALSE, Forms\Form::class));
			$this->template->add('step', $this->getCurrentStep());
			$this->template->add('menu', $this->menu);
			$this->template->add('currentForm', $this->getComponent('step_' . $this->getCurrentStep()));

			// Check if translator is available
			if ($this->getTranslator() instanceof Localization\ITranslator) {
				$this->template->setTranslator($this->getTranslator());
			}

			// If template was not defined before...
			if ($this->template->getFile() === NULL) {
				// ...try to get base component template file
				$templateFile = $this->templateFile !== NULL ? $this->templateFile : __DIR__ . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . 'default.latte';
				$this->template->setFile($templateFile);
			}
		}
	}

	/**
	 * Render control
	 *
	 * @return Application\UI\ITemplate
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	public function render()
	{
		$template = $this->getTemplate();

		// Check if control has template
		if ($template instanceof Nette\Bridges\ApplicationLatte\Template) {
			$this->beforeRender();

			// Render component template
			$template->render();

		} else {
			throw new Exceptions\InvalidStateException('Control is without template.');
		}
	}

	/**
	 * @param string $name
	 * @param Forms\Form|callable $form
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function addForm(string $name, $form)
	{
		if (!($form instanceof Forms\Form) && !is_callable($form)) {
			$formType = is_object($form) ? get_class($form) : gettype($form);

			throw new Exceptions\InvalidArgumentException(sprintf('Expected instance of Nette\Application\UI\Form, %s passed instead', $formType));
		}

		if ($form instanceof Forms\Form && $form->getElementPrototype()->getAttribute('enctype') !== NULL) {
			throw new Exceptions\InvalidArgumentException('StepForm cannot handle forms with file upload!');
		}

		$counter = $this->getTotalSteps() + 1;

		if ($form instanceof Forms\Form) {
			$this->addComponent($form, 'step_' . $counter);

		} else {
			$this->formCallbacks[$counter] = $form;
		}

		$this->menu[$counter] = $name;
	}

	/**
	 * @param int|NULL $step
	 *
	 * @return Forms\Form
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function getForm(int $step = NULL) : Forms\Form
	{
		if ($step === NULL) {
			$step = $this->getCurrentStep();
		}

		$this->validateStep($step);

		if (!$form = $this->getComponent('step_' . $step, FALSE)) {
			if (isset($this->formCallbacks[$step]) && is_callable($this->formCallbacks[$step])) {
				$form = call_user_func($this->formCallbacks[$step]);
			}

			if (!$form instanceof Forms\Form) {
				$formType = is_object($form) ? get_class($form) : gettype($form);

				throw new Exceptions\InvalidArgumentException(sprintf('[STEP %s] Returned value of factory is not instance of Nette\Application\UI\Form, %s passed instead.', $step, $formType));
			}

			$this->addComponent($form, 'step_' . $step);
		}

		$form->onValidate[] = [$this, 'triggerValidate'];
		$form->onSubmit[] = [$this, 'triggerSubmit'];
		$form->onSuccess[] = [$this, 'triggerSuccess'];
		$form->onError[] = [$this, 'triggerError'];

		$formData = $this->storage->get($this->getSessionKey($step));

		if ($formData instanceof Utils\ArrayHash) {
			if ($formData->_form !== $form->getName()) {
				throw new Exceptions\InvalidArgumentException('Existing results do not match the given form!');
			}

			if ($this->fillWithDefaults) {
				$form->setDefaults($this->getValues($step));
			}
		}

		return $form;
	}

	/**
	 * @param int $step
	 *
	 * @return void
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function setStep(int $step)
	{
		$this->validateStep($step);

		if ($this->useLinkSteps) {
			$this->step = $step;

		} else {
			$this->storage->set('step', $step);
		}
	}

	/**
	 * @return int
	 */
	public function getCurrentStep() : int
	{
		if ($this->useLinkSteps) {
			return (int) $this->step;
		}

		return (int) $this->storage->get('step', 1);
	}

	/**
	 * Change default step form template path
	 *
	 * @param string $templateFile
	 *
	 * @return void
	 */
	public function setTemplateFile(string $templateFile)
	{
		// Check if template file exists...
		if (!is_file($templateFile)) {
			$templateFile = $this->transformToTemplateFilePath($templateFile);
		}

		$this->templateFile = $templateFile;
	}

	/**
	 * @param Localization\ITranslator $translator
	 *
	 * @return void
	 */
	public function setTranslator(Localization\ITranslator $translator)
	{
		$this->translator = $translator;
	}

	/**
	 * @return Localization\ITranslator|NULL
	 */
	public function getTranslator()
	{
		if ($this->translator instanceof Localization\ITranslator) {
			return $this->translator;
		}

		return NULL;
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return void
	 */
	public function triggerValidate(Forms\Form $form)
	{
		$this->onValidate($this, $form);
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return void
	 */
	public function triggerSubmit(Forms\Form $form)
	{
		$this->onSubmit($this, $form);
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return void
	 */
	public function triggerSuccess(Forms\Form $form)
	{
		$this->onSuccess($this, $form);
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return void
	 */
	public function triggerError(Forms\Form $form)
	{
		$this->onError($this, $form);
	}

	/**
	 * @param Forms\Controls\SubmitButton $button
	 *
	 * @return void
	 */
	public function submitStep(Forms\Controls\SubmitButton $button)
	{
		$form = $button->getForm();

		$submitName = $button->getName();

		if ($submitName === self::BUTTON_PREV_NAME) {
			$currentStep = $this->getCurrentStep();
			$this->setStep(--$currentStep);

		} elseif ($submitName === self::BUTTON_NEXT_NAME && $form->isValid()) {
			$this->handleSubmit($form);

			$currentStep = $this->getCurrentStep();
			$this->setStep(++$currentStep);

		} elseif ($submitName === self::BUTTON_FINISH_NAME && $form->isValid()) {
			$this->handleSubmit($form);

			$this->clearValues();
		}
	}

	/**
	 * @param int $step
	 */
	public function handleChangeStep(int $step)
	{
		$this->setStep($step);

		$this->redrawControl('stepNavi');
		$this->redrawControl('activeStep');

		$this->redirect('this');
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return void
	 */
	private function handleSubmit(Forms\Form $form)
	{
		$toStore = new Utils\ArrayHash;
		$toStore->values = $form->getValues(TRUE);
		$toStore->_form = $form->getName();
		$toStore->_valid = $form->isValid();

		$currentStep = $this->getCurrentStep();
		$step = $this->getFormStepNumber($form);
		$key = $this->getSessionKey($step);

		$this->storage->set($key, $toStore);

		if (!$form->isSuccess()) {
			return;
		}

		if ($currentStep === $this->getTotalSteps()) {
			$form->onSubmit[] = function() {
				$this->onComplete($this);
			};
		}
	}

	/**
	 * @param int|NULL $step
	 * @param bool $asArray
	 *
	 * @return Utils\ArrayHash|array|NULL
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	public function getValues(int $step = NULL, bool $asArray = FALSE)
	{
		$this->validateStep($step);

		$key = $this->getSessionKey($step);

		if (($values = $this->storage->get($key, FALSE)) && $values instanceof Utils\ArrayHash) {
			if ($asArray) {
				return (array) $values->values;

			} else {
				return Utils\ArrayHash::from($values->values);
			}
		}

		return NULL;
	}

	/**
	 * @param int $step
	 *
	 * @return bool
	 */
	public function hasValues(int $step) : bool
	{
		return $this->getValues($step) !== NULL;
	}

	/**
	 * @return void
	 */
	public function clearValues()
	{
		$this->storage->clearAll();

		foreach($this->getComponents(FALSE, Forms\Form::class) as $form) {
			$form->setValues([], TRUE);
		}
	}

	/**
	 * @param Forms\Form $form
	 *
	 * @return int
	 *
	 * @throws Exceptions\InvalidStateException
	 */
	private function getFormStepNumber(Forms\Form $form)
	{
		$counter = 1;

		foreach ($this->getComponents(FALSE, Forms\Form::class) as $component) {
			if ($component === $form) {
				return $counter;
			}

			$counter++;
		}

		throw new Exceptions\InvalidStateException('This form has not been added!');
	}

	/**
	 * @param Forms\Form $form
	 */
	private function attachButtonsCallbacks(Forms\Form $form)
	{
		/** @var Forms\Controls\SubmitButton $control */
		foreach ($form->getComponents(FALSE, Forms\Controls\SubmitButton::class) as $control) {
			if (!in_array($control->getName(), [self::BUTTON_PREV_NAME, self::BUTTON_NEXT_NAME, self::BUTTON_FINISH_NAME], TRUE)) {
				continue;
			}

			$control->onClick[] = [$this, 'submitStep'];
			$control->onInvalidClick[] = [$this, 'submitStep'];

			if ($control->getName() === self::BUTTON_PREV_NAME) {
				$control->setValidationScope(FALSE);
			}
		}
	}

	/**
	 * @param int $step
	 *
	 * @return string
	 */
	private function getSessionKey(int $step) : string
	{
		return sprintf(self::STEP_SESSION_KEY, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return bool
	 *
	 * @throws Exceptions\InvalidArgumentException
	 */
	private function validateStep(int $step) : bool
	{
		if ($step < 1 || $step > $this->getTotalSteps()) {
			throw new Exceptions\InvalidArgumentException(sprintf('Step must be an integer in range between 1 and %s!', $this->getTotalSteps()));
		}

		return TRUE;
	}

	/**
	 * @return int
	 */
	private function getTotalSteps() : int
	{
		return count($this->menu);
	}

	/**
	 * @param string $templateFile
	 *
	 * @return string
	 *
	 * @throws Exceptions\FileNotFoundException
	 */
	private function transformToTemplateFilePath(string $templateFile) : string
	{
		// Get component actual dir
		$dir = dirname($this->getReflection()->getFileName());

		$templateName = preg_replace('/.latte/', '', $templateFile);

		// ...check if extension template is used
		if (is_file($dir . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $templateName . '.latte')) {
			return $dir . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR . $templateName . '.latte';
		}

		// ...if not throw exception
		throw new Exceptions\FileNotFoundException(sprintf('Template file "%s" was not found.', $templateFile));
	}
}
