<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;
Use Bolt\Extension\cdowdy\betterthumbs\Helpers\FilePathHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


use Bolt\Version as Version;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;


class BetterThumbsBackendController implements ControllerProviderInterface {
	protected $app;

	protected $configHelper;

	private $config;

	protected $_expected = [ 'jpg', 'jpeg', 'png', 'tiff', 'tif', 'gif', 'bmp' ];

	protected $_mimeTypes = [ 'image/jpeg', 'image/png', 'image/x-ms-bmp', 'image/gif', 'image/tiff' ];


	/**
	 * Initiate the controller with Bolt Application instance and extension config.
	 *
	 * @param array $config
	 */
	public function __construct( array $config )
	{
		$this->config = $config;
	}


	/**
	 * @param Application $app
	 *
	 * @return ControllerCollection
	 */
	public function connect( Application $app )
	{
		/** @var ControllerCollection $ctr */
		$ctr = $app['controllers_factory'];

		$ctr->get( '/files', [ $this, 'bthumbsFiles' ] )
		    ->bind( 'betterthumbs_files' );

		$ctr->get( '/docs', [ $this, 'bthumbsDocs' ] )
		    ->bind( 'betterthumbs_docs' );

		$ctr->post( '/files/delete', [ $this, 'deleteSingle' ] )
		    ->bind( 'betterthumbs_delete' );

		$ctr->post( '/files/delete/all', [ $this, 'deleteAll' ] )
		    ->bind( 'betterthumbs_delete_all' );

		$ctr->get( '/files/prime', [ $this, 'primeCache' ] )
		    ->bind( 'betterthumbs_prime' );

		$ctr->post( '/files/prime/do', [ $this, 'doPrime' ] )
		    ->bind( 'betterthumbs_doPrime' );

		$ctr->before( [ $this, 'before' ] );


		return $ctr;
	}


	/**
	 * @param Request     $request
	 * @param Application $app
	 *
	 * @return null|RedirectResponse
	 */
	public function before( Request $request, Application $app )
	{
		if ( ! $app['users']->isAllowed( 'files' ) ) {
			/** @var UrlGeneratorInterface $generator */
			$generator = $app['url_generator'];

			return new RedirectResponse( $generator->generate( 'dashboard' ), Response::HTTP_SEE_OTHER );
		}

		return null;
	}


	/**
	 * @param Application $app
	 *
	 * @return mixed
	 */
	public function bthumbsDocs( Application $app )
	{
		return $app['twig']->render( '@betterthumbs/betterthumbs.docs.html.twig' );
	}


	/**
	 * @param Application $app
	 *
	 * @return mixed
	 */
	public function bthumbsFiles( Application $app )
	{

		$filespath =  (new FilePathHelper( $app ) )->boltFilesPath() . '/.cache';
		$adapter    = new Local( $filespath );
		$filesystem = new Filesystem( $adapter );

		$fsListContents = $filesystem->listContents( null, true );

		$cachedImage = [];
		$allFiles    = [];

		foreach ( $fsListContents as $item ) {
			// get the directory name from filesystem
			// prepare this to just get the "basename" for display in our templates
			$parts = pathinfo( $item['dirname'] );

			if ( $item['type'] == 'file' ) {

				// get the directory name recursively
				// make the value the "basename" to display in our templates
				// Original we made an image with glide doing
				// $app['betterthumbs']->makeImage( $item['dirname'], ['w' => 200, 'h' => 133, 'fit' => 'crop' ] )
				// This caused apache & lightspeed to choke in the backend and not display the cached image
				// because an htaccess rule rewrites the cache route of /cache
				// since we save to /files/.cache/ this caused issues since it was captured by the rewrite rule.
				// Now just serve a file from disk and not worry about the cached image even though they may
				// differ
				$cachedImage += [
					$item['dirname'] => [
						'name' => $parts['basename'],
						'path' => $item['dirname']
					]
				];
				// get all the files and prepare them for deletion
				$allFiles[] = $item['dirname'];
			}

		}

		// make sure the cachedImage array has no duplicates or empty members
		$cachedUnique = array_unique( $cachedImage, SORT_REGULAR );
		// make sure the allFiles array has no duplicates and json_encode it
		$allFilesUnique = json_encode( array_unique( $allFiles, SORT_REGULAR ) );

		$context = [
			'allFiles'    => $allFilesUnique,
			'cachedImage' => $cachedUnique,
            'bthumbsRoute' => $this->buildProperExtensionPath($app)
		];

		return $app['twig']->render('@betterthumbs/bolts.base.html.twig', $context );
	}


	/**
	 * @param Application $app
	 * @param Request     $request
	 *
	 * @return mixed
	 */
	public function deleteSingle( Application $app, Request $request )
	{

		return $app['betterthumbs']->deleteCache( $request->request->get( 'img' ) );

	}


	/**
	 * @param Application $app
	 * @param Request     $request
	 *
	 * @return array
	 */
	public function deleteAll( Application $app, Request $request )
	{
		$betterthumbs = $app['betterthumbs'];

		$all     = $request->request->get( 'all' );
		$removed = [];
		foreach ( $all as $key => $image ) {
			$removed = $betterthumbs->deleteCache( $image );
		}

		return $removed;
	}

	/**
	 * @param Application $app
	 *
	 * @return mixed
	 */
	public function primeCache( Application $app )
	{
		$adapter    = new Local( (new FilePathHelper( $app ) )->boltFilesPath() );
		$filesystem = new Filesystem( $adapter );

		$fileList = $filesystem->listContents( null, true );

		// load in the config helper and get the driver set in the config
		$configHelper = new ConfigHelper( $this->config );
		$imageDriver  = $configHelper->setImageDriver();
		// get the accepted mime types array
		$expectedMimes = $this->checkAccpetedTypes( $imageDriver );


		$files = [];

		// only loop over objects in the filespath that are of the type 'file'
		// don't get any images or files from the cache directory
		// finally use flysystem, get each objects mimetype and compare it to the accepted ones
		// found from $expectedMimes
		foreach ( $fileList as $object ) {

			if ( $object['type'] == 'file'
			     && ! preg_match_all( '/^.cache\//i', $object['dirname'] )
			     && in_array( strtolower( $filesystem->getMimetype( $object['path'] ) ), $expectedMimes )
			) {

				$files[] = [
					'filename'  => $object['basename'],
					'located'   => $object['dirname'],
					'imagePath' => $object['path'],
					'mimeType'  => $filesystem->getMimetype( $object['path'] ),
//                    'isCached' => $app['betterthumbs']->cache
				];
			}
		}


		$config         = $this->config;
		$selectOptions  = [];
		$presetSettings = [];

		// for each config array check to make sure a key of modifications exists.
		// also get the presets in the presets array that don't have a modifications key
		foreach ( $config as $key => $values ) {

			if ( is_array( $values ) && array_key_exists( 'modifications', $values ) ) {
				$selectOptions[] = $key;
			}

			if ( is_array( $values ) && $key == strtolower( 'presets' ) ) {
				$presetSettings[] = $key;
			}
		}


		$context = [

			'allFiles'  => $files,
			'extConfig' => $selectOptions,
			'presets'   => $presetSettings,
            'bthumbsRoute' => $this->buildProperExtensionPath($app)
		];

		return $app['twig']->render( '@betterthumbs/prime/betterthumbs.prime.html.twig', $context );
	}

	/**
	 * @param Application $app
	 * @param             $image
	 * @param             $modifications
	 *
	 * @return mixed
	 */
	public function primeImage( Application $app, $image, $modifications )
	{
		$betterthumbs = $app['betterthumbs'];
		$betterthumbs->setCacheWithFileExtensions( true );

		return $betterthumbs->makeImage( $image, $modifications );
	}

	/**
	 * @param Application $app
	 * @param Request     $request
	 *
	 * @return JsonResponse
	 */
	public function doPrime( Application $app, Request $request )
	{
		$modType    = $request->get( 'modType' );
		$configName = $request->get( 'configName' );
		$image      = $request->get( 'image' );

		$configHelper = new ConfigHelper( $this->config );
		$presetMods   = $configHelper->setPresets();


		$primed = [];

		switch ( $modType ) {

			case 'presets':
				foreach ( $presetMods as $preset ) {
					$primed[] .= $this->primeImage( $app, $image, $preset );
				}
				break;
			case 'config':
				$configMods = $this->config[ $configName ]['modifications'];
				foreach ( $configMods as $parameters ) {
					$primed[] .= $this->primeImage( $app, $image, $parameters );
				}
				break;
			case 'single':

				if ( isset( $this->config[ $configName ] ) ) {
					$singleParams = $this->config[ $configName ];
					$primed[]     = $this->primeImage( $app, $image, $singleParams );
				}

				break;
		}

		return new JsonResponse( $primed );
	}

    /**
     * @param $driver
     * @return array|string
     */
	protected function checkAccpetedTypes( $driver )
	{
		$acceptedTypes = '';

		$gdAccepted = [ 'image/jpeg', 'image/png', 'image/gif' ];
		$imAccepted = [ 'image/jpeg', 'image/png', 'image/gif', 'image/x-ms-bmp', 'image/tiff' ];

		if ( $driver === 'gd' ) {
			$acceptedTypes = $gdAccepted;
		}

		if ( $driver === 'imagick' ) {
			$acceptedTypes = $imAccepted;
		}

		return $acceptedTypes;

	}

    /**
     * @param Application $app
     * @return string
     *
     * a method to check to make sure bolt is greater than or equal too 3.3.0 since they changed backend page routes
     */
	public function buildProperExtensionPath(Application $app )
    {
        // bolt devs like to break things in a non backwards manner in minor releases. So we have to
        // hackishly build a url here.. check for the version number and then move on cause you know... who likes
        // to do things in a normal sane backwards compatible manner? not bolt devs for extension authors

        $urlGenerator = $app['url_generator'];
        $dashboardRoute = $urlGenerator->generate( 'dashboard' );

        if (Version::compare('3.3.0', '>=')) {
            $extensionsRoute = 'extensions';
        } else {
            $extensionsRoute = 'extend';
        }

        return $dashboardRoute . '/' . $extensionsRoute ;
    }
}