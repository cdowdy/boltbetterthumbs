<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Nut;

use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
Use Bolt\Extension\cdowdy\betterthumbs\Helpers\FilePathHelper;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;



class BetterThumbsCommand extends Command
{
    protected $app;

    /**
     * BetterThumbsCommand constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }


    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('betterthumbs:cacheClear')
            ->setDescription('Clear The BetterThumbs Cache')
            ->setHelp('This Command Allows you to remove all or one specific file from the BetterThumbs Cache')
            ->addArgument('type', InputArgument::REQUIRED, 'Clear The Entire Cache Or Just One (1) Image');
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $type = $input->getArgument('type');

        // get the path to bolt's files and append '.cache' for betterthumbs to it
        $filespath = (new FilePathHelper( $this->app ) )->boltFilesPath() . '/.cache' ;

        // pass that path to the Flysytem Adapter
        $adapter    = new Local( $filespath );

        // Initialize flysystem with our cache path
        $Filesystem = new Filesystem( $adapter );

        // get all the files located in our cache directory
        $allFiles = array_diff(scandir($filespath), array('.', '..'));



        if ($type === 'all') {

            $io->newLine();

            $output->writeln([
                '<info>Removing all Images From The BetterThumbs Cache</info>',
                '<info>===============================================</info>'
            ]);
            foreach ($allFiles as $file ) {

                if ($Filesystem->has( $file ) ) {
                    $Filesystem->deleteDir( $file );
                }

            }

            $io->success('all files removed from cache');

        } else {

            if ( $Filesystem->has($type) ) {

                $io->newLine();

                $output->writeln([
                    '<info>removing ' . $input->getArgument('type') . ' from the BetterThumbs cache</info>'
                ]);

                $Filesystem->deleteDir( $type );

                $io->success(
                     $input->getArgument('type') . ' Removed from the BetterThumbs cache'
                );

            } else {

                $io->error(
                     $input->getArgument('type') . ' Isn\'t in the BetterThumbs cache'
                );

            }

        }

    }

}