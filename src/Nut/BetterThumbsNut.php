<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Nut;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class BetterThumbsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('betterthumbs:cacheClear')
            ->setDescription('Clear The BetterThumbs Cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $text = 'clearing betterthumbs cache';

        $output->writeln($text);
    }

}