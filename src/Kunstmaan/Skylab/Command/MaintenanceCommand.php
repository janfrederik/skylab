<?php
namespace Kunstmaan\Skylab\Command;

use Kunstmaan\Skylab\Skeleton\AbstractSkeleton;
use Kunstmaan\Skylab\Skeleton\BaseSkeleton;
use Symfony\Component\Console\Input\InputOption;

/**
 * MaintenanceCommand
 */
class MaintenanceCommand extends AbstractCommand
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->addDefaults()
            ->setName('maintenance')
            ->setDescription('Run maintenance on all Skylab projects')
            ->addOption("--quick", null, InputOption::VALUE_NONE, 'If set, no fixperms will be executed')
            ->setHelp(<<<EOT
The <info>maintenance</info> command will run the maintenance commands of all skeletons on a project. Most notably, it
will create the apache config files and make sure the the databases are available.

<info>php skylab.phar maintenance</info>

EOT
            );
    }

    /**
     *
     */
    protected function doExecute()
    {
        $this->dialogProvider->logStep("Running preMaintenance");
        $this->skeletonProvider->skeletonLoop(function (AbstractSkeleton $theSkeleton) {
            $this->dialogProvider->logTask("Running preMaintenance for skeleton " . $theSkeleton->getName());
            $theSkeleton->preMaintenance();
        });

        $this->fileSystemProvider->projectsLoop(function ($project) {
            $this->dialogProvider->logStep("Running maintenance on " . $project["name"]);
            $this->skeletonProvider->skeletonLoop(function (AbstractSkeleton $theSkeleton) use ($project) {
                $this->dialogProvider->logTask("Running maintenance for skeleton " . $theSkeleton->getName());
                $theSkeleton->maintenance($project);
            }, new \ArrayObject($project["skeletons"]));
        });

        $this->dialogProvider->logStep("Running postMaintenance");
        $this->skeletonProvider->skeletonLoop(function (AbstractSkeleton $theSkeleton) {
            $this->dialogProvider->logTask("Running postMaintenance for skeleton " . $theSkeleton->getName());
            $theSkeleton->postMaintenance();
        });

        if (!$this->input->getOption('quick')) {
            $this->fileSystemProvider->projectsLoop(function ($project) {
                $this->dialogProvider->logStep("Running fixperms on " . $project["name"]);
                /** @var BaseSkeleton $abstractSkeleton */
                $abstractSkeleton = $this->skeletonProvider->findSkeleton('base');
                $abstractSkeleton->setPermissions(new \ArrayObject($project));
            });
        }
    }
}
