<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Bolt\Extension\cdowdy\betterthumbs\Helpers;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use League\Glide\Urls\UrlBuilderFactory;
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

        $ctr->post('/files/delete', [$this, 'deleteSingle'])
            ->bind('betterthumbs_delete');

        $ctr->post('/files/delete/all', [$this, 'deleteAll'])
            ->bind('betterthumbs_delete_all');


        return $ctr;
    }

    public function bthumbsDocs(Application $app)
    {
        return $app['twig']->render('betterthumbs.docs.html.twig');
    }

    public function bthumbsFiles(Application $app)
    {
        $configHelper = $configHelper = new ConfigHelper($this->config);
        $signkey = $configHelper->setSignKey();

        $filespath = $app['resources']->getPath('filespath') . '/.cache';
        $allFiles = array_diff(scandir($filespath), array('.', '..'));

        $secureURL = UrlBuilderFactory::create('/', $signkey );
        foreach ($allFiles as $file) {
          $cachedImg[] =  $secureURL->getUrl($file, ['w' => 200, 'h' => 200, 'fit' => 'contain' ]);
        }

        $context = [
            'path' => $allFiles,
        ];


        return $app['twig']->render('betterthumbs.files.html.twig', $context);
    }

    public function deleteSingle(Application $app, Request $request)
    {
        $betterthumbs = $app['betterthumbs'];

        return $betterthumbs->deleteCache($request->request->get('img'));

    }

    public function deleteAll(Application $app, Request $request)
    {
        $betterthumbs = $app['betterthumbs'];

        $all = $request->request->get('all') ;
        $removed = [];
        foreach ($all as $key => $image ) {
            $removed = $betterthumbs->deleteCache($image);
        }

        return $removed;


    }
}