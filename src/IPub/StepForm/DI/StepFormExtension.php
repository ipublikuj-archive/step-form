<?php
/**
 * StepFormExtension.php
 *
 * @copyright      More in license.md
 * @license        http://www.ipublikuj.eu
 * @author         Adam Kadlec http://www.ipublikuj.eu
 * @package        iPublikuj:StepForm!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           04.02.17
 */

declare(strict_types = 1);

namespace IPub\StepForm\DI;

use Nette;
use Nette\DI;
use Nette\PhpGenerator as Code;

use IPub;
use IPub\StepForm;
use IPub\StepForm\Components;
use IPub\StepForm\Storage;

/**
 * Step form extension container
 *
 * @package        iPublikuj:StepForm!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class StepFormExtension extends DI\CompilerExtension
{
	/**
	 * @var array
	 */
	private $defaults = [
		'templateFile' => NULL
	];

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration()
	{
		parent::loadConfiguration();

		// Get container builder
		$builder = $this->getContainerBuilder();
		// Get extension configuration
		$configuration = $this->getConfig($this->defaults);

		// Storage factory
		$builder->addDefinition($this->prefix('storage.factory'))
			->setClass(Storage\StorageFactory::class);

		// Session storage
		$builder->addDefinition($this->prefix('storage.session'))
			->setClass(Storage\Session::class)
			->setImplement(Storage\ISession::class);

		// Define component factory
		$stepFormFactory = $builder->addDefinition($this->prefix('stepForm'))
			->setClass(Components\Control::class)
			->setImplement(Components\IControl::class)
			->setArguments([new Code\PhpLiteral('$formName'), new Code\PhpLiteral('$templateFile')])
			->setInject(TRUE);

		if ($configuration['templateFile']) {
			$stepFormFactory->addSetup('$service->setTemplateFile(?)', [$configuration['templateFile']]);
		}
	}

	/**
	 * @param Nette\Configurator $config
	 * @param string $extensionName
	 *
	 * @return void
	 */
	public static function register(Nette\Configurator $config, string $extensionName = 'stepForm')
	{
		$config->onCompile[] = function (Nette\Configurator $config, DI\Compiler $compiler) use ($extensionName) {
			$compiler->addExtension($extensionName, new StepFormExtension());
		};
	}
}
