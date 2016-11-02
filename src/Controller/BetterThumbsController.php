<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;



use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\RedirectableUrlMatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use League\Glide\Server;
use League\Glide\ServerFactory;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;



class BetterThumbsController implements ControllerProviderInterface
{
    protected $app;

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

        $ctr->match('{path}', [$this, 'makeImage']);

        return $ctr;
    }


    public function makeImage(Application $app, $path, Request $request  )
    {
        $adapter = new Local($app['resources']->getPath('filespath') );
        $Filesystem = new Filesystem($adapter);

        $server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(),
            'source' => $Filesystem,
            'cache' => $Filesystem,

            'source_path_prefix' => $Filesystem,
//            'source_path_prefix' => 'files',
            'cache_path_prefix' => '.cache',

            'base_url' => 'img',
            'driver' => 'gd',
        ]);

        ob_end_clean();
        return $server->getImageResponse($path, $request->query->all());
    }
}