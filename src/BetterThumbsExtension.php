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


use Bolt\Tests\Provider\PagerServiceProviderTest;
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



    public function image( $file, $name = 'betterthumbs', array $options = [] )
    {
        $app = $this->getContainer();
        $config = $this->getConfig();

        $configName = $this->getNamedConfig($name);
        // this lets us create a srcset array
        $srcset = new SrcsetHandler($config, $configName);
        // this is used to create our regular 'src' (fallback) image
        $thumbHelper = new Thumbnail($config, $configName);

        // placeholder for our modification parameters while testing out secure URL's
        $params = ['p' => 'medium'];

        // Set our Source Image file name and it's modifications
        $thumbHelper->setSourceImage($file)->setModifications($params);
        // build the secure url for the src/fallback image
        $url = $thumbHelper->buildSecureURL();


        // Get The Modification Parameters passed in through the config and merge them with the ones
        // passed in from the template
        $parameters = $this->getModificationParams($configName, $options);
        // get the options passed in to the parameters and prepare it for our srcset array.
        $optionWidths = $this->flatten_array($parameters, 'w');
        // get the resolutions passed in from our config file
        $resolutions = $srcset->getResolutions();
        // get the width or density type passed in from our config file
//        $widthDensity = $srcset->getWidthDensity();


        $thumb = $srcset->createSrcset($file, $optionWidths, $resolutions, $parameters);

        $context = [
            'srcImg' => $url,
            'srcset' => $thumb,
        ];

        $renderTemplate = $this->renderTemplate('thumb.html.twig', $context);

        return new \Twig_Markup($renderTemplate, 'UTF-8');
    }

   function combineWidthHeightArrays($option1, $option2, $padValue )
   {

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
     * @param $option
     * @return array
     * take the option passed in from the template. Check if its in an array.
     * if its not an array make it one.
     * also check to make sure there is actual data in the array with array_filter
     * we only want to print a class if there is something actually there.
     */
    protected function optionToArray( $option ) {
        // check if the option that we need to be an array is in fact in an array
        $isArray = is_array($option) ? $option : array($option);

        // return the array and make sure it is not empty
        return array_filter($isArray);

    }

    function getModificationParams($config, array $options = [] )
    {
        $extConfig = $this->getConfig();
        $configName = $this->getNamedConfig($config);
        $modificationParams = isset($extConfig[$configName]['modifications']) ? $extConfig[$configName]['modifications'] : [] ;
//        $modificationParams = array_key_exists('modifications', $extConfig[$configName]);
        $presetParams = $extConfig['presets'];

        // replace parameters in 'presets' with the params in a named config
        if (isset($modificationParams) || array_key_exists('modifications', $extConfig[$configName]) ) {
            $defaults = array_merge($presetParams, $modificationParams);
        } else {
            $defaults = $presetParams;
        }

        return array_merge($defaults, $options);
    }

    // TODO: take these options, merge them if they are set in a template then pass them to the srcset handler
    function getOptions($filename, $config, $options =[])
    {
        $configName = $this->getNamedConfig($config);

        $altText = $this->getAltText($configName, $filename);
        $class = $this->getHTMLClass($configName);
        $sizes = $srcsetHandler->getSizesAttrib($configName);
        $resolutions = $this->getResolutions();
        $widthDensity = $this->getWidthDensity();

        $defaults = [
            'widthDensity' => $widthDensity,
            'resolutions' => $defaultRes,
            'sizes' => $sizes,
            'altText' => $altText,
            'lazyLoad' => $lazyLoaded,
            'class' => $class

        ];

        $defOptions = array_merge($defaults, $options);

        return $defOptions;
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
     * Flatten The multidimensional array in the extensions config under Presets.
     *
     * @param array $array
     * @param string $fallbackOption
     * @return array
     */
    protected function flatten_array(array $array, $fallbackOption)
    {

        $fallback = [];

            foreach ($array as $key => $value) {
                if (array_key_exists($fallbackOption, $value )) {
                    $fallback[] = $value[$fallbackOption];
                } else {
                    $fallback[] = 0;
                }

            }

        return $fallback;
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
