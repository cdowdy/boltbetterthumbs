<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Bolt\Extension\cdowdy\betterthumbs\Helpers;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use League\Glide\ServerFactory;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;


use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;



class BetterThumbsController implements ControllerProviderInterface
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

        $ctr->get('/{path}', [$this, 'makeImage'])
            ->assert('path', '.+')
            ->bind('betterthumbs');

        return $ctr;
    }


    public function makeImage(Application $app, $path, Request $request  )
    {
        // get the path to the bolt installations files and use Flysystem to get that path
        $adapter = new Local($app['resources']->getPath('filespath') );
        $Filesystem = new Filesystem($adapter);

        // pull in my currently messy helper file and use $configHelper as the accessor to our config file
        $configHelper = new ConfigHelper($this->config);

        // Set the Image Driver
        $ImageDriver = $configHelper->setImageDriver();

        // Set any presets -> fallback to the ones I've set if there is non

        $presets = $configHelper->setPresets();
        // set and merge any defaults
        $defaults = $configHelper->setDefaults();
        // set and get the max image size:
        $configHelper->setMaxImageSize($this->config['security']['max_image_size']);
        $maxImgSize = $configHelper->getMaxImageSize();

        // create a glide server
        $server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(),
            'source' => $Filesystem,
            'cache' => $Filesystem,
            'cache_path_prefix' => '.cache',
            'max_image_size' => $maxImgSize,
            'watermarks' => $Filesystem,
            'base_url' => '/img/',
            'driver' => $ImageDriver,
        ]);
        // set our defaults and presets with glide's setters
        $server->setDefaults($defaults);
        $server->setPresets($presets);
        // set a switch to use the cached image in the future
//        $server->setCacheWithFileExtensions(true);


        // make sure the URL is signed with our key before allowing manipulations done to the thumbnail
        try {


            $signkey = $configHelper->setSignKey();

                // Validate HTTP signature
            SignatureFactory::create($signkey)->validateRequest($path,  $request->query->all());


        } catch (SignatureException $e) {
//            throw new SignatureException( $e->getMessage() );
            // the 401 works but maybe we should actually send bolts not found image?
            return new Response('Operation Not Allowed', Response::HTTP_UNAUTHORIZED);
        }

        // ob_clean / ob_end_clean is needed here ¯\_(ツ)_/¯
        ob_clean();
        return $server->getImageResponse($path, $request->query->all());
    }
}