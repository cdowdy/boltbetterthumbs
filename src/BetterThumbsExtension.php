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
use Bolt\Extension\cdowdy\betterthumbs\Handler\SrcsetHandler;
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


        $configHelper = new ConfigHelper($config);

        $configName = $this->getNamedConfig($name);

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
        $srcset = new SrcsetHandler($config);
//        $urlBuilder = UrlBuilderFactory::create('/', $signkey);


        // placeholder for our modification parameters while testing out secure URL's
        $params = ['p' => 'medium'];

        // Generate a Secure URL
//        $url = $urlBuilder->getUrl($file, $params );
        $url = $srcset->buildSecureURL($file, $params);

        $widthHeights = $this->getWidthsHeights($configName, 'w');

        $resolutions = $srcset->getResolutions($configName);
        $sizes = $srcset->getSizesAttrib($configName);
        $widthDensity = $srcset->getWidthDensity($configName);

        $context = [
            'img' => $url,
            'configName' => $configName,
            'widthHeights' => $widthHeights,
            'res' => $resolutions,
            'sizes' => $sizes,
            'widthDensity' => $widthDensity,
//            'sizes' => $sizeAttrib,
        ];

        $renderTemplate = $this->renderTemplate('thumb.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }


    /**
     * @param $option
     * @param $optionType
     * @param $fallback
     * @return mixed
     */
    protected function checkIndex( $option, $optionType, $fallback )
    {
        return ( isset( $option[$optionType]) ? $option[$optionType] : $fallback );
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
     * @param $option1
     * @param $option2
     * @param $padValue
     * @return array
     */
    protected function getCombinedArray($option1, $option2, $padValue)
    {
        $option1Count = count($option1);
        $option2Count = count($option2);

        if ($option1Count != $option2Count) {
            $option1Array = array_pad($option1, $option2Count, $padValue);
        } else {
            $option1Array = $option1;
        }

        if ($option2Count != $option1Count) {
            $option2Array = array_pad($option2, $option1Count, $padValue);
        } else {
            $option2Array = $option2;
        }

        $combinedArray = array_combine($option1Array, $option2Array);

        return $combinedArray;

    }




    /**
     * You can't rely on bolts methods to insert javascript/css in the location you want.
     * So we have to hack around it. Use the Snippet Class with their location methods and insert
     * Picturefill into the head. Add a check to make sure the script isn't loaded more than once ($_scriptAdded)
     * and stop the insertion of the files multiple times because bolt's registerAssets method will blindly insert
     * the files on every page
     *
     */

    protected function addAssets()
    {
        $app = $this->getContainer();

        $config = $this->getConfig();

        $pfill = $config['picturefill'];

        $extPath = $app['resources']->getUrl('extensions');

        $vendor = 'vendor/cdowdy/';
        $extName = 'betterthumbs/';

        $pictureFillJS = $extPath . $vendor . $extName . 'picturefill/' . $this->_currentPictureFill . '/picturefill.min.js';
        $pictureFill = <<<PFILL
<script src="{$pictureFillJS}" async defer></script>
PFILL;
        $asset = new Snippet();
        $asset->setCallback($pictureFill)
            ->setZone(ZONE::FRONTEND)
            ->setLocation(Target::AFTER_HEAD_CSS);


        // add picturefill script only once for each time the extension is used
        if ($pfill){
            if ($this->_scriptAdded == FALSE ) {
                $app['asset.queue.snippet']->add($asset);
                $this->_scriptAdded = TRUE;
            } else {

                $this->_scriptAdded = TRUE;
            }
        }
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
