<?php
namespace Sumkabum\Magento2RemoveOrphanImages\Console;

use Exception;
use Magento\Framework\App\ObjectManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveOrphanImages extends Command
{
    const OPTION_DELETE = 'delete';
    protected function configure()
    {
        $this->setName('sumkabum:remove-orphan-images');
        $this->addOption(self::OPTION_DELETE, null, InputOption::VALUE_OPTIONAL);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Sumkabum\Magento2RemoveOrphanImages\Service\RemoveOrphanImages $removeOrphanImages */
        $removeOrphanImages = ObjectManager::getInstance()->get(\Sumkabum\Magento2RemoveOrphanImages\Service\RemoveOrphanImages::class);
        $removeOrphanImages->output = $output;
        $removeOrphanImages->run($input->getOption(self::OPTION_DELETE));
        $output->writeln('done');
        return 0;
    }
}
