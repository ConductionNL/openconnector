<?php

namespace OCA\openconnector\lib\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AuthenticationExtension extends AbstractExtension
{
	public function getFunctions()
	{
		return [
			new TwigFunction(name: 'oauthToken', callable: [AuthenticationRuntime::class, 'oauthToken']),
			new TwigFunction(name: 'jwtToken', callable: [AuthenticationRuntime::class, 'jwtToken']),
		];
		//return parent::getFunctions(); // TODO: Change the autogenerated stub
	}
}
