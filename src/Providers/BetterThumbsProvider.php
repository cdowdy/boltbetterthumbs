<?php
namespace Bolt\Extension\cdowdy\betterthumbs\Providers;


use Silex\Application;
use Silex\ServiceProviderInterface;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;
use League\Glide\ServerFactory;
use League\Glide\Responses\SymfonyResponseFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;


class BetterThumbsProvider implements ServiceProviderInterface
{
    private $config;

    /**
     * BetterThumbsProvider constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }


    public function register(Application $app)
    {
        $app['betterthumbs'] = $app->share(
            function ($app) {
                $adapter = new Local($app['resources']->getPath('filespath') );
                $Filesystem = new Filesystem($adapter);

                // pull in my currently messy helper file and use $configHelper as the accessor to our config file
                $configHelper = new ConfigHelper( $this->config);

                // Set the Image Driver
                $ImageDriver = $configHelper->setImageDriver();

                // set and get the max image size:
                $configHelper->setMaxImageSize( $this->config['security']['max_image_size']);
                $maxImgSize = $configHelper->getMaxImageSize();
                return ServerFactory::create([
                    'response' => new SymfonyResponseFactory(),
                    'source' => $Filesystem,
                    'cache' => $Filesystem,
                    'cache_path_prefix' => '.cache',
                    'max_image_size' => $maxImgSize,
                    'watermarks' => $Filesystem,
                    'base_url' => '/img/',
                    'driver' => $ImageDriver,
                ]);
            }
        );
    }

    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }

}