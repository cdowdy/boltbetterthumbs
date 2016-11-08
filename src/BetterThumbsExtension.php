<?php

namespace Bolt\Extension\cdowdy\betterthumbs;

//use Bolt\Application;
use Bolt\Asset\File\JavaScript;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Bolt\Controller\Zone;
use Bolt\Extension\cdowdy\betterthumbs\Controller\BetterThumbsController;
use Bolt\Extension\SimpleExtension;
use Bolt\Filesystem as BoltFilesystem;

use League\Glide\Urls\UrlBuilderFactory;






/**
 * BetterThumbs extension class.
 *
 * @author Cory Dowdy <cory@corydowdy.com>
 */
class BetterThumbsExtension extends SimpleExtension
{

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


    /**
     * The callback function when {{ my_twig_function() }} is used in a template.
     *
     * @return string
     */
    public function image( $file  )
    {
        $app = $this->getContainer();
        $config = $this->getConfig();
        $defs = $config['defaults'];


        // TODO: make sure you remove this dev sign key with a good one :) before pushing to production
        $signkey = 'v-LK4WCdhcfcc%jt*VC2cj%nVpu+xQKvLUA%H86kRVk_4bgG8&CWM#k*b_7MUJpmTc=4GFmKFp7=K%67je-skxC5vz+r#xT?62tT?Aw%FtQ4Y3gvnwHTwqhxUh89wCa_';

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
        $params = [
            'mark' => 'bthumb-watermark.png',
            'markw'=> '60w',

        ];

        // Generate a Secure URL
        $url = $urlBuilder->getUrl($file, $params );

        $context = [
            'img' => $url,
            'test' => $url,
            'file' => $file,
            'params' => $params,
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
     * @return array
     */
    protected function getDefaultConfig()
    {
        return [
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
