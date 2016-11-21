bolt extension that uses [Glide](http://glide.thephpleague.com/) for thumbnails. 

Will give you srcset and picture element support :)  

---------------------------------------
Bad boy is no where near production ready :)  


-----------------------------------------
In your template place this tag wherever you want an image. Responsive Images using `srcset` is the default output:  
 
 example using a record image

```twig  
{{ img( record.image, 'presets' ) }}  
```  

example using a file from "files"  
  
```twig  
{{ img( 'image-from-files.jpg', 'presets' ) }}   
```

If you don't give it a named config (the 'presets' after the file name above), The extension will use 4 presets with these widths:  

* 175 pixels wide
* 350 pixels wide
* 700 pixels wide
* 1400 pixels wide  

Along with those widths a `sizes` attribute is set at 100vw, the width descriptor is used and the "fit" of the image is set to stretch to allow images to upscale if need be to 1400 pixels. The URL will also be signed to prevent mass image manipulation attacks, or image manipulations done to an image that you didn't want or ok.  

Here is how the markup will look:  

```html 
<img sizes="100vw"  
    srcset="/img/filename-here.jpg?p=small&s=324da5bd33624470fd09fd670aad0341 175w,
    /img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc 350w,
    /img/filename-here.jpg?p=large&s=DtCRxm0D0tO48OTQEGb81xeaucwrEFdD 700w,
    /img/filename-here.jpg?p=xlarge&s=yXQAuVfPXmINowtyWqXMSykY6NO6s8be 1400w"
    src="/img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc"
    alt="alt text">  
```  

## Extension Setup  
__Image Driver__  

This extension allows you to use either GD or ImageMagick for your image manipulations. The default setting is 'gd'. This is the same image driver Bolt's standard thumbs use and if your thumbs work in your current Bolt site then the default setting will be good for you here too. If you wish to use ImageMagick you must have php's imagick extension installed in your system. To change your image driver see the `Image_Driver` setting.  

```yaml 
Image_Driver: 'gd'  
```  

__Security__ 

By default each thumbnail URL is signed. Without this signature an image cannot be generated. This prevents flood attacks and hundred/thousands etc of images from being generated and bringing your server down or filling up disk space. You'll need to set a key in the security section:  
```yaml
security:
  secure_thumbs: true
  secure_sign_key: 'your  key goes here'
```  

__Defaults__  

You can set defaults that will be done to each and every image manipulation through this extension. You can set the quality, image format etc. To match Bolt's thumbnail settings a default quality of '80' is set.  
```yaml 
defaults: 
    q: 80
```  

__Presets__  

Image presets are groups of image manipulations you can quickly use instead of a named config. These are also the fallback settings if you don't set any modifications in your named config. So you could create a named config and leave out the modifications part and the modifications used will be whatever you've set in 'Presets'.  
```yaml
presets: &presets
  small:
    w: 175
    fit: stretch
  medium:
    w: 350
    fit: stretch
  large:
    w: 700
    fit: stretch
  xlarge:
    w: 1400
    fit: stretch
```

## Config Setup  
TODO:  
* save_data
* class
* altText
* widthDensity
* sizes
* resolutions  

### Modifications: 
The settings for each thumbnail are declarative. Meaning for every modification you wish to make to that particular thumbnail you must have it in the config. Example:  

```yaml
namedConfig:
# other settings here!
  modifications:
    small:
      w: 175
      fit: stretch
    medium:
      w: 350
      h: 350
      fit: stretch
    large:
      w: 700
      fit: stretch
    xlarge:
      w: 1400
      fit: stretch
```  
This will give you four (4) thumbnails with widths of 175, 350, 700 and 1400 with a fit of 'stretch'. The Second thumbnail (named 'medium' in this example) will also have a height of 350 pixels.  
 
 Using the ``fit`` of 'stretch' allows us to upscale images to fit a certain width. In this example an image that is 800px wide will be upscaled to 1400.  
 
 The available modifications are:  
 
 | Modification Name | Function |
 | ------------- | ------ |
 | Orientation        | or       |
 | Crop               | crop    |
 | Width               | w    |
 | Height               | h    |
 | Fit                 | fit |
 | Device Pixel Ratio | dpr |
 | Brightness         | bri |
 | Contrast         | con |
 | Gamma           | gam |
 | Sharpen          | sharp |
 | Blur             | blur |
 | Pixelate         | pixel |
 | Filter           | filt |
 | Watermark Path | mark |
 | Watermark Width | markw |
 | Watermark Height | markh |
 | Watermark X-offset | markx |
 | Watermark Y-offset | marky |  
 | Watermark Padding | markpad |
 | Watermark Position | markpos |
 | Watermark Alpha | markalpha |
 | Background      | bg |
 | Border           | border |
 | Quality          | q |
 | Format          | fm |  
 
 
 All available modifications and what they mean can be found at [Glide's Website](http://glide.thephpleague.com/1.0/api/quick-reference/).