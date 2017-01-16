<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;



use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;


use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


use League\Glide\Signatures\SignatureFactory;
use League\Glide\Signatures\SignatureException;


use League\Flysystem\Adapter\Local;
use League\Flysystem\Memory\MemoryAdapter;
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

    public function makeImage(Application $app, $path, Request $request )
    {
        // pull in my currently messy helper file and use $configHelper as the accessor to our config file
        $configHelper = new ConfigHelper($this->config);
        $presets = $configHelper->setPresets();
        // set and merge any defaults
        $defaults = $configHelper->setDefaults();

        $FSAdapter = $this->fsAdapter($app);

        $app['betterthumbs']->setDefaults($defaults);
        $app['betterthumbs']->setPresets($presets);
        $app['betterthumbs']->setCache($FSAdapter);
//        $app['betterthumbs']->setCacheWithFileExtensions(true);

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
        return $app['betterthumbs']->getImageResponse($path, $request->query->all());
    }


    protected function fsAdapter(Application $app)
    {
        $configHelper = new ConfigHelper($this->config);
        $adapter = strtolower($configHelper->setFilesystemAdapter());


        if ( $adapter == 'local' ) {
            return new Filesystem( new Local($app['resources']->getPath('filespath') ) );
        }

        if ( $adapter == 'memory' ) {
            return new Filesystem( new MemoryAdapter() );
        }
    }
}