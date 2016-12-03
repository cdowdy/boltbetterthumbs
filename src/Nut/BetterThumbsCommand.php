<?php


namespace Bolt\Extension\cdowdy\betterthumbs\Nut;

//use Symfony\Component\Console\Command\Command;
//use Symfony\Component\Console\Input\InputArgument;
//use Symfony\Component\Console\Input\InputInterface;
//use Symfony\Component\Console\Input\InputOption;
//use Symfony\Component\Console\Output\OutputInterface;
//use Bolt\Filesystem\Iterator\RecursiveDirectoryIterator;
use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

//use RecursiveDirectoryIterator;
//use RecursiveIteratorIterator;
//use SplFileInfo;

use League\Glide\ServerFactory;



use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;


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
        $type = $input->getArgument('type');
//
        if ($type == 'all') {
//            $this->doDeleteAll();
            $this->doDelete();
            $output->writeln([
                'Removing all Images From The BetterThumbs Cache',
                '======================================'
            ]);
        } else {
//            $this->doDeleteAll($input->getArgument('type'));
            $this->doDelete($input->getArgument('type'));
            $output->writeln([
                'removing ' . $input->getArgument('type') . ' from the BetterThumbs cache'
            ]);
        }

//        $output->writeln($text);
    }

    protected function doDelete($default = NULL)
    {
        $adapter = new Local($this->app['resources']->getPath('filespath') );
        $Filesystem = new Filesystem($adapter);

        $filespath = $this->app['resources']->getPath('filespath') . '/.cache';
        $allFiles = array_diff(scandir($filespath), array('.', '..'));

        $server = ServerFactory::create([
            'source' => $Filesystem,
            'cache' => $Filesystem,
            'cache_path_prefix' => '.cache',
        ]);
        if( $default ) {
            $server->deleteCache($default);
        } else {
            foreach ($allFiles as $file ) {
                $server->deleteCache($file );
            }
        }
    }

//    protected function deleteCachedImages($dir)
//    {
//        if (false === file_exists($dir)) {
//            return false;
//        }
//
//        /** @var SplFileInfo[] $files */
//        $files = new RecursiveIteratorIterator(
//            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
//            RecursiveIteratorIterator::CHILD_FIRST
//        );
//        foreach ($files as $fileinfo) {
//            if ($fileinfo->isDir()) {
//                if (false === rmdir($fileinfo->getRealPath())) {
//                    return false;
//                }
//            } else {
//                if (false === unlink($fileinfo->getRealPath())) {
//                    return false;
//                }
//            }
//        }
//        return rmdir($dir);
//    }
//
//    protected function doDeleteAll($default = NULL )
//    {
//        $filespath = $this->app['resources']->getPath('filespath') . '/.cache';
//        $allFiles = array_diff(scandir($filespath), array('.', '..'));
//
//       if( $default ) {
//           $this->deleteCachedImages($filespath . '/' . $default);
//       } else {
//           foreach ($allFiles as $file ) {
//               $this->deleteCachedImages($filespath . '/' . $file );
//           }
//       }
//    }

}