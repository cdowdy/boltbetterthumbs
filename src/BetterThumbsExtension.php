<?php

namespace Bolt\Extension\cdowdy\betterthumbs;

//use Bolt\Application;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Bolt\Filesystem as BoltFilesystem;

use Bolt\Extension\cdowdy\betterthumbs\Controller\BetterThumbsController;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\Thumbnail;
use Bolt\Extension\cdowdy\betterthumbs\Helpers\ConfigHelper;


use League\Glide\Urls\UrlBuilderFactory;



/**
 * BetterThumbs extension class.
 *
 * @author Cory Dowdy <cory@corydowdy.com>
 */
class BetterThumbsExtension extends SimpleExtension
{

    private $_currentPictureFill = '3.0.2';
    private $_scriptAdded = FALSE;

    /**
     * {@inheritdoc}
     */
    protected function registerTwigPaths()
    {
        return ['templates'];
    }


    /**
     * {@inheritdoc}
     */
    protected function registerTwigFunctions()
    {
        $options = ['is_safe' => ['html']];
        $this->getConfig();
        return [
            'img' => ['image',  $options ],

        ];
    }



    public function image( $file, $name = 'betterthumbs' )
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

//        $configOptions = new Helpers\Thumbnail($config);

        $configHelper = new ConfigHelper($config);


//       $configOptions->setConfigName($name);
//       $configName = $configOptions->getConfigName();
        $configName = $this->getNamedConfig($name);

//        $signkey = $config['security']['secure_sign_key'];
        $signkey = $configHelper->setSignKey();

        /**
         * set the "base url" for the Secure URL to '/' since if we use the "base_url" option of '/img/'
         * we get double '/img//img/' in our URL's
         * /img//img/file-name.jpg?s=signature-here
         *
         * We don't want that. We want urls like:
         * /img/file-name.jpg?s=signature-here
         *
         * so in our template for secure urls we need to have '/img{{ img }}'
         *
         * conversely if we set the base url to an empty string '', it has the same result as setting it to '/'
         */
        $urlBuilder = UrlBuilderFactory::create('/', $signkey);


        // placeholder for our modification parameters while testing out secure URL's
        $params = ['p' => 'xlarge',];

        // Generate a Secure URL
        $url = $urlBuilder->getUrl($file, $params );

        $widthHeights = $this->getWidthsHeights($configName, 'w');

        $context = [
            'img' => $url,
            'configName' => $configName,
            'widthHeights' => $widthHeights,
//            'widthDensity' => $widthDensity,
//            'sizes' => $sizeAttrib,
        ];

        $renderTemplate = $this->renderTemplate('thumb.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }

    /**
     * @return array
     */
    protected function registerFrontendControllers()
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        return [
            '/img' => new BetterThumbsController($config),

        ];
    }

    /**
     * @param $name
     * @return mixed
     *
     * get a "named config" from the extensions config file
     */
    protected function getNamedConfig($name)
    {

        if (empty( $name ) ) {
            $configName = 'betterthumbs';
        } else {
            $configName = $name ;
        }

        return  $configName ;
    }


    /**
     * @param $fallbackOption
     * @return array
     */
    protected function getPresetFallbacks($fallbackOption)
    {
        $configFile = $this->getConfig();
        $presets = $this->getNamedConfig('presets');
        $fallback = [];

        foreach ($configFile[$presets] as $fallbackItem) {
            $fallbackElement = $fallbackItem[$fallbackOption];
            array_push($fallback, $fallbackElement);
        }

        return $fallback;
    }

    /**
     * @param $config
     * @param $option
     * @return array
     */
    protected function getWidthsHeights($config, $option )
    {
        $extConfig = $this->getNamedConfig($config);
        $configFile = $this->getConfig();
        $namedConfig = $configFile[$extConfig]['modifications'];

        $configOption = $namedConfig[$option];

        $grabFallback = $this->getPresetFallbacks($option);

        if (isset($configOption) && !empty($configOption)) {
            $configParam = $configOption;
        } else {
            $configParam = $grabFallback ;
        }

        return $configParam;
    }



    /**
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
            'Image_Driver' => 'gd',
            'security' => [
                'secure_thumbs' => true,
                'secure_sign_key' => ''
            ],
            'presets' => [
                'small' => [
                    'w' => 175,
                    'fit' => 'contain'
                ],
                'medium' => [
                    'w' => 350,
                    'fit' => 'contain'
                ],
                'large' => [
                    'w' => 700,
                    'fit' => 'contain'
                ],
                'xlarge' => [
                    'w' => 1400,
                    'fit' => 'stretch'
                ],
            ],
            'default' => [
                'widths' => [ 320, 480, 768 ],
                'heights' => [ 0 ],
                'widthDensity' => 'w',
                'sizes' => [ '100vw'  ],
                'cropping' => 'resize',
                'altText' => '',
                'class' => ''
            ],
            'allowed_types' => [
                'webp',
                'jpeg',
                'jpg',
                'png',
                'gif',
                'jxr'
            ]
        ];
    }

    public function isSafe()
    {
        return true;
    }


}
