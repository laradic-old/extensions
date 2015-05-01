<?php namespace Laradic\Extensions\Commands;

use Laradic\Extensions\Contracts\Extension;
use Laradic\Support\Command;

class InstallExtension extends Command {

    public $extension;

    /**
     * Create a new command instance.
     *
     * @param \Laradic\Extensions\Contracts\Extension $extension
     */
	public function __construct(Extension $extension)
	{
		/** @var \Laradic\Extensions\Extension $extension */
        $this->extension = $extension;
	}



}
