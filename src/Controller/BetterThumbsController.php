<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;


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
        $adapter = new Local($app['resources']->getPath('filespath') );
        $Filesystem = new Filesystem($adapter);

        $ImageDriver = $this->config['Image_Driver'];

        $defaultSettings = $this->config['defaults'];



        $server = ServerFactory::create([
            'response' => new SymfonyResponseFactory(),
            'source' => $Filesystem,
            'cache' => $Filesystem,

//            'source_path_prefix' => $Filesystem,
            'source_path_prefix' => 'files',
            'cache_path_prefix' => '.cache',

            'watermarks' => $Filesystem,

            'base_url' => '/img/',
            'driver' => $ImageDriver,
        ]);

        // make sure the URL is signed with our key before allowing manipulations done to the thumbnail
        try {
            // TODO: make sure you remove this dev sign key with a good one :) before pushing to production
            // Set complicated sign key
//            $signkey = 'v-LK4WCdhcfcc%jt*VC2cj%nVpu+xQKvLUA%H86kRVk_4bgG8&CWM#k*b_7MUJpmTc=4GFmKFp7=K%67je-skxC5vz+r#xT?62tT?Aw%FtQ4Y3gvnwHTwqhxUh89wCa_';

            $signkey = $this->config['secure_sign_key'];

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