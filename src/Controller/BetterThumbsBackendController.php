<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Bolt\Extension\cdowdy\betterthumbs\Helpers;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;



use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;



class BetterThumbsBackendController implements ControllerProviderInterface
{
    protected $app;

    protected $configHelper;

    private $config;


    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }



    public function connect(Application $app)
    {
        /** @var ControllerCollection $ctr */
        $ctr = $app['controllers_factory'];

        $ctr->get('/files', [$this, 'bthumbsFiles'])
            ->bind('betterthumbs_files');

        $ctr->get('/docs', [$this, 'bthumbsDocs'])
            ->bind('betterthumbs_docs');

        return $ctr;
    }

    public function bthumbsDocs(Application $app)
    {
        return $app['twig']->render('betterthumbs.docs.html.twig');
    }

    public function bthumbsFiles(Application $app)
    {
        // get the path to the bolt installations files and use Flysystem to get that path
        $adapter = new Local($app['resources']->getPath('filespath') );
        $Filesystem = new Filesystem($adapter);
        $filespath = $app['resources']->getPath('filespath') . '/.cache';
        $allFiles = array_diff(scandir($filespath), array('.', '..'));

        $contents = $Filesystem->listContents();

        $context = [
            'path' => $allFiles,
            'fsystem' => $contents,
        ];


        return $app['twig']->render('betterthumbs.files.html.twig', $context);
    }
}