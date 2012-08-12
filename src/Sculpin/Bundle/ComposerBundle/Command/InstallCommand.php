<?php

/*
 * This file is a part of Sculpin.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sculpin\Bundle\ComposerBundle\Command;

use Composer\Factory;
use Composer\Installer;
use Composer\IO\ConsoleIO;
use Composer\Json\JsonFile;
use Composer\Repository\FilesystemRepository;
use Sculpin\Bundle\ComposerBundle\Console\ComposerAwareApplicationInterface;
use Sculpin\Core\Console\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Install Command.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Ryan Weaver <ryan@knplabs.com>
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Beau Simensen <beau@dflydev.com>
 */
class InstallCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('composer:install')
            ->setDescription('Parses the composer.json file and downloads the needed dependencies.')
            ->setDefinition(array(
                new InputOption('prefer-source', null, InputOption::VALUE_NONE, 'Forces installation from package sources when possible, including VCS information.'),
                new InputOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs the operations but will not execute anything (implicitly enables --verbose).'),
                new InputOption('dev', null, InputOption::VALUE_NONE, 'Enables installation of dev-require packages.'),
                new InputOption('no-scripts', null, InputOption::VALUE_NONE, 'Skips the execution of all scripts defined in composer.json file.'),
            ))
            ->setHelp(<<<EOT
The <info>install</info> command reads the composer.json file from the
current directory, processes it, and downloads and installs all the
libraries and dependencies outlined in that file.

<info>php composer.phar install</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!($this->getApplication() instanceof ComposerAwareApplicationInterface)) {
            throw new \RuntimeException('Application must be instance of ComposerAwareApplicationInterface');
        }
        $io = new ConsoleIO($input, $output, $this->getApplication()->getHelperSet());
        $composer = Factory::create($io);
        if ($this->getApplication()->internallyInstalledRepositoryEnabled()) {
            $internalRepositoryFile = $this->getApplication()->getInternalVendorRoot().'/composer/installed.json';
            $filesystemRepository = new FilesystemRepository(new JsonFile($internalRepositoryFile));
        } else {
            $filesystemRepository = null;
        }

        $install = Installer::create($io, $composer);

        $install
            ->setDryRun($input->getOption('dry-run'))
            ->setVerbose($input->getOption('verbose'))
            ->setPreferSource($input->getOption('prefer-source'))
            ->setDevMode($input->getOption('dev'))
            ->setRunScripts(!$input->getOption('no-scripts'))
            ->setAdditionalInstalledRepository($filesystemRepository);

        return $install->run() ? 0 : 1;
    }
}
