<?php

namespace OCA\OpenConnector\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MappingExtension extends AbstractExtension
{
	public function getFunctions(): array
	{
		return [
			new TwigFunction(name: 'executeMapping', callable: [MappingRuntime::class, 'executeMapping']),
		];
		//return parent::getFunctions(); // TODO: Change the autogenerated stub
	}
}
