<?php

namespace Bolt\Extension\cdowdy\betterthumbs\Controller;


use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Bolt\Extension\cdowdy\betterthumbs\Helpers;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use League\Glide\Urls\UrlBuilderFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\Plugin\ListPaths;
use League\Flysystem\Memory\MemoryAdapter;



class BetterThumbsBackendController implements ControllerProviderInterface
{
    protected $app;

    protected $configHelper;

    private $config;

    protected $_expected = [ 'jpg', 'jpeg', 'png', 'tiff', 'gif', 'bmp' ];


    /**
     * Initiate the controller with Bolt Application instance and extension config.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }


    /**
     * @param Application $app
     * @return ControllerCollection
     */
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

        $ctr->get('/files/prime', [$this, 'primeCache'])
            ->bind('betterthumbs_prime');

        $ctr->post('/files/prime/do', [$this, 'doPrime'])
            ->bind('betterthumbs_doPrime');

        $ctr->before([$this, 'before']);


        return $ctr;
    }


    /**
     * @param Request $request
     * @param Application $app
     * @return null|RedirectResponse
     */
    public function before(Request $request, Application $app)
    {
        if (!$app['users']->isAllowed('dashboard')) {
            /** @var UrlGeneratorInterface $generator */
            $generator = $app['url_generator'];
            return new RedirectResponse($generator->generate('dashboard'), Response::HTTP_SEE_OTHER);
        }
        return null;
    }


    /**
     * @param Application $app
     * @return mixed
     */
    public function bthumbsDocs(Application $app)
    {
        return $app['twig']->render('betterthumbs.docs.html.twig');
    }


    /**
     * @param Application $app
     * @return mixed
     */
    public function bthumbsFiles(Application $app)
    {

        $filespath = $app['resources']->getPath('filespath') . '/.cache';

        $adapter = new Local($filespath);
        $filesystem = new Filesystem($adapter);

        $fsListContents = $filesystem->listContents(null, true);

        $cachedImage = [];
        $allFiles = [];

        foreach ($fsListContents as $item) {
            // get the directory name from filesystem
            // prepare this to just get the "basename" for display in our templates
            $parts = pathinfo( $item['dirname']);

            if ($item['type'] == 'file' && in_array(strtolower($item['extension']), $this->_expected) ) {

                // get the directory name recursively
                // make the value the "basename" to display in our templates
                $cachedImage += [
                    $app['betterthumbs']->makeImage( $item['dirname'], ['w' => 200, 'h' => 133, 'fit' => 'crop' ] ) => [
                        'name' => $parts['basename'],
                        'path' => $item['dirname']
                    ]
                ];
                // get all the files and prepare them for deletion
                $allFiles[] = $item['dirname'];
            }

        }

        // make sure the cachedImage array has no duplicates or empty members
        $cachedUnique = array_unique($cachedImage, SORT_REGULAR);
        // make sure the allFiles array has no duplicates and json_encode it
        $allFilesUnique = json_encode(array_unique($allFiles, SORT_REGULAR));

        $context = [
            'allFiles' => $allFilesUnique,
            'cachedImage' => $cachedUnique,
        ];

        return $app['twig']->render('betterthumbs.files.html.twig', $context);
    }


    /**
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function deleteSingle(Application $app, Request $request)
    {

        return $app['betterthumbs']->deleteCache($request->request->get('img'));

    }


    /**
     * @param Application $app
     * @param Request $request
     * @return array
     */
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

    public function primeCache(Application $app)
    {
        $adapter = new Local($app['resources']->getPath('filespath') );
        $filesystem = new Filesystem($adapter);

        $fileList = $filesystem->listContents(null, true);

        $excludeDir = '/^.cache\//i';

        $files = [];

        foreach ( $fileList as $object  ) {

            if ($object['type'] == 'file'
                && in_array(strtolower($object['extension']), $this->_expected )
                && !preg_match_all('/^.cache\//i', $object['dirname'])) {

                $files[] = [
                    'filename' => $object['basename'],
                    'located' => $object['dirname']
                ];
            }
        }


        $config = $this->config;
        $context = [

            'allFiles' => $files,
            'filePaths' => $fileList,
            'config' => $config,
        ];

        return $app['twig']->render('betterthumbs.prime.html.twig', $context);
    }

}