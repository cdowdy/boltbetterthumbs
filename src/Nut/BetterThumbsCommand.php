<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Nut;

use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class BetterThumbsCommand extends Command
{
    protected $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure()
    {
        $this
            ->setName('betterthumbs:cacheClear')
            ->setDescription('Clear The BetterThumbs Cache')
            ->setHelp('This Command Allows you to remove all or one specific file from the BetterThumbs Cache')
            ->addArgument('type', InputArgument::REQUIRED, 'Clear The Entire Cache Or Just One (1) Image');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
//
        if ($type == 'all') {
//            $this->doDeleteAll();
            $this->doDelete();
            $output->writeln([
                '<info>Removing all Images From The BetterThumbs Cache</info>',
                '<info>======================================</info>'
            ]);
//            $io->success('all Images Have Been Removed From BetterThumbs Cache');
        } else {
//            $this->doDeleteAll($input->getArgument('type'));
            $this->doDelete($input->getArgument('type'));
            $output->writeln([
                '<info>removing ' . $input->getArgument('type') . ' from the BetterThumbs cache</info>'
            ]);
//            $io->success($input->getArgument('type') . 'Has Been Removed From BetterThumbs Cache');
        }

//        $output->writeln($text);
    }

    protected function doDelete($default = NULL)
    {
        $filespath = $this->app['resources']->getPath('filespath') . '/.cache';
        $allFiles = array_diff(scandir($filespath), array('.', '..'));


        $server = $this->app['betterthumbs'];
        if( $default ) {
            $server->deleteCache($default);
        } else {
            foreach ($allFiles as $file ) {
                $server->deleteCache($file );
            }
        }
    }
}