bolt extension that uses [Glide](http://glide.thephpleague.com/) for thumbnails. 

Will give you srcset and picture element support :)  

---------------------------------------
Bad boy is no where near production ready :)  


-----------------------------------------
In your template place this tag wherever you want an image. Responsive Images using `srcset` is the default output:  
 
 example using a record image

```twig  
{{ img( record.image, 'default' ) }}  
```  

example using a file from "files"  
  
```twig  
{{ img( 'image-from-files.jpg', 'default' ) }}   
```

If you don't give it a named config (the 'default' after the file name above), The extension will use 4 presets with these widths:  

* 175 pixels wide
* 350 pixels wide
* 700 pixels wide
* 1400 pixels wide  

Along with those widths a `sizes` attribute is set at 100vw, the width descriptor is used and the "fit" of the image is set to stretch to allow images to upscale if need be to 1400 pixels. The URL will also be signed to prevent mass image manipulation attacks, or image manipulations done to an image that you didn't want or ok.  

Here is how the markup will look:  

```html 
<img sizes="100vw"  
    srcset="/img/filename-here.jpg?p=small&s=324da5bd33624470fd09fd670aad0341 175w,
    /img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc 350w,,
    /img/filename-here.jpg?p=large&s=DtCRxm0D0tO48OTQEGb81xeaucwrEFdD 700w,
    /img/filename-here.jpg?p=xlarge&s=yXQAuVfPXmINowtyWqXMSykY6NO6s8be 1400w"
    src="/img/filename-here.jpg?p=medium&s=a21a21ea8dc43a94c0666a20ccaefbcc"
    alt="alt text">  
```
