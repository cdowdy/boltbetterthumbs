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
* resolutions  

__Img Tag Attributes__  

You can set HTML classes, ID's, and data-attributes in your named config. Classes you wish to provide should be in array, and data-attribs in a hash.  

```yaml 
namedConfg:  
  class: [ 'your-class', 'anotherClass' ]  
  id: 'js-hook'  
  data_attrib: { 'responsive': 'images'}
```  

The rendered img tag will look like so:  

```html 
<img id="js-hook" class="your-class anotherClass" data-responsive="images"  
    sizes="100vw"  
    srcset="/img/filename-here.jpg?p=small&s=324da5bd33624470fd09fd670aad0341 175w,
    /img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc 350w,
    /img/filename-here.jpg?p=large&s=DtCRxm0D0tO48OTQEGb81xeaucwrEFdD 700w,
    /img/filename-here.jpg?p=xlarge&s=yXQAuVfPXmINowtyWqXMSykY6NO6s8be 1400w"
    src="/img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc"
    alt="alt text">  
```  

__Alt Text:__  

Alt text should be included for each image tag. If this option is left blank it will fallback to the filename. In most instances this is not good alt text. So you the user should supply the alt text. In instances where alt text would be redundant you can turn off alt text by using a tilde (~) in the extensions config or the word FLASE in your templates. To help determine if your alt text is redundant or sufficient see http://webaim.org/techniques/alttext/#context  

```yaml  
namedConfig:  
  #other config settings
  altText: 'alt text here'  
  # or to turn off alt text if you're using the figure wrapping element  
  altText: ~  
```  

__Sizes:__  

Sizes is an array. If you are unfamiliar with this image attribute see: [Responsive Images 101, Part 5: Sizes](https://cloudfour.com/thinks/responsive-images-101-part-5-sizes/). If no sizes are provided and you are using the width descriptor it will fall back to `100vw`.  

```yaml  
namedConfig:  
  #other config settings
  sizes: [ '(max-width: 480px) 100vw', '(max-width: 900px) 33vw', '254px']   
```  

Rendered img tag:  
  
```html  
<img id="js-hook" class="your-class anotherClass" data-responsive="images"  
    sizes="(max-width: 480px) 100vw, (max-width: 900px) 33vw, 254px"    
    srcset="/img/filename-here.jpg?p=small&s=324da5bd33624470fd09fd670aad0341 175w,
      /img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc 350w,
      /img/filename-here.jpg?p=large&s=DtCRxm0D0tO48OTQEGb81xeaucwrEFdD 700w,
      /img/filename-here.jpg?p=xlarge&s=yXQAuVfPXmINowtyWqXMSykY6NO6s8be 1400w"
    src="/img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc"
    alt="alt text">  
```  

__Width Density:__  

Decide if you want to use the image width or screen density for your responsive images. This extension defaults to using the width descriptor ('w'). For more information on these see: [Responsive Images 101, Part 4: Srcset Width Descriptors](https://cloudfour.com/thinks/responsive-images-101-part-4-srcset-width-descriptors/) and [Responsive Images 101, Part 3: Srcset Display Density](https://cloudfour.com/thinks/responsive-images-101-part-3-srcset-display-density/)  

```yaml  
namedConfig:  
  #other settings here
  widthDensity: 'w' # or 'x'
```

__Modifications:__  

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