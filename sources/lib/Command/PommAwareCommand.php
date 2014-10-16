<?php
/*
 * This file is part of Pomm's Cli package.
 *
 * (c) 2014 Grégoire HUBERT <hubert.greg@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PommProject\Cli\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

use PommProject\Foundation\Pomm;
use PommProject\Foundation\Session;
use PommProject\Foundation\Inspector\InspectorPooler;

use PommProject\Cli\Exception\CliException;

class PommAwareCommand extends Command
{
    private $session;

    protected $config_file;
    protected $config_name;

    /**
     * execute
     *
     * Set pomm dependent variables.
     *
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->config_file = $input->getOption('bootstrap-file');
        $this->config_name = $input->getArgument('config-name');
    }

    /**
     * configureRequiredArguments
     *
     * In order to keep the same argument order for all commands, it is
     * necessary to be able to declare base required fields before subcommands.
     *
     * @access protected
     * @return PommAwareCommand $this
     */
    protected function configureRequiredArguments()
    {
        $this
            ->addArgument(
                'config-name',
                InputArgument::REQUIRED,
                'Database configuration name to open a session.'
            )
            ;

        return $this;
    }

    /**
     * configureOptionals
     *
     * In order to keep the same argument order for all commands, it is
     * necessary to be able to declare base required fields before subcommands.
     *
     * @access protected
     * @return PommAwareCommand $this
     */
    protected function configureOptionals()
    {
        $this
            ->addOption(
            'bootstrap-file',
            '-b',
            InputArgument::OPTIONAL,
            'Complete path of the CLI bootstrap file.',
            sprintf("%s/.pomm_cli_bootstrap.php", getenv('PWD'))
        )
        ;

        return $this;
    }

    /**
     * configure
     *
     * @see command
     */
    protected function configure()
    {
        $this
            ->configureRequiredArguments()
            ->configureOptionals()
            ;
    }

    /**
     * loadSession
     *
     * Load session bootstrap file.
     *
     * @access protected
     * @param  string  $config_name
     * @return Session
     */
    protected function loadSession()
    {
        if (!file_exists($this->config_file)) {
            throw new CliException(sprintf("Could not load configuration '%s'.", $this->config_file));
        }

        $pomm = require $this->config_file;

        if (!$pomm instanceof Pomm) {
            throw new CliException(sprintf("Invalid configuration. Bootstrap file must return a Pomm instance."));
        }

        return $pomm->getSession($this->config_name);
    }

    /**
     * getSession
     *
     * Return a session.
     *
     * @access protected
     * @param  sring   $name
     * @return Session
     */
    protected function getSession()
    {
        if ($this->session === null) {
            $this->session = $this->loadSession()->registerClientPooler(new InspectorPooler());
        }

        return $this->session;
    }

    /**
     * setSession
     *
     * When testing, it is useful to provide directly the session to be used.
     *
     * @access public
     * @param  Session          $session
     * @return PommAwareCommand
     */
    public function setSession(Session $session)
    {
        $this->session = $session;

        return $this;
    }
}
