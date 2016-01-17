<?php

namespace mtoolkit\view;

/*
 * This file is part of MToolkit.
 *
 * MToolkit is free software: you can redistribute it and/or modify
 * it under the terms of the LGNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * MToolkit is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * LGNU Lesser General Public License for more details.
 *
 * You should have received a copy of the LGNU Lesser General Public License
 * along with MToolkit.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author  Michele Pagnin
 */

use mtoolkit\core\enum\AspectRatioMode;
use mtoolkit\core\MFileInfo;

class MImage
{
    /**
     * @var string|null
     */
    private $fileName = null;
    
    /**
     * @var resource
     */
    private $resource = null;
    
    /**
     * @var int
     */
    private $height = -1;
    
    /**
     * @var int
     */
    private $width = -1;
    
    /**
     * @var string|null
     */
    private $type = null;

    /**
     * Saves the image to the file with the given <i>$fileName</i>, 
     * using the given image file <i>$format</i> and quality factor. <br />
     * The <i>$quality</i> factor must be in the range 0 to 100 or -1. 
     * Specify 0 to obtain small compressed files, 100 for large uncompressed files, 
     * and -1 (the default) to use the default settings.<br />
     * Returns true if the image was successfully saved; otherwise returns false.
     * 
     * @param string $fileName The path to save the file to. If not set or NULL, the raw image stream will be outputted directly.
     * @param string $format
     * @param int $quality
     * @return boolean
     */
    public function save( $fileName, $format = 'png', $quality = -1 )
    {
        $format = ($this->type != null ? $this->type : $format);
        $return = false;

        switch( $format )
        {
            case 'gif':
                $return = imagegif( $this->resource, $fileName );
                break;
            case 'jpg':
                $return = imagejpeg( $this->resource, $fileName, $quality );
                break;
            case 'bmp':
                $return = imagewbmp( $this->resource, $fileName );
                break;
            default:
                $return = imagepng( $this->resource, $fileName, $quality );
                break;
        }

        return $return;
    }

    /**
     * Something like that:
     * <code>
     * $string = 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
     * . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
     * . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
     * . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==';
     * $string = base64_decode($string);
     * $im = imagecreatefromstring($string);
     * </code>
     * 
     * @param string $string
     * @return \MToolkit\View\MImage
     */
    public static function fromData( $string )
    {
        $newResource = imagecreatefromstring( $string );

        return MImage::fromResource( $newResource );
    }

    public static function fromResource( $resource )
    {
        $newImage = new MImage();
        $newImage->resource = $resource;
        $newImage->width = imagesx( $resource );
        $newImage->height = imagesy( $resource );
        return $newImage;
    }

    /**
     * Resizes the color table to contain colorCount entries.
     * 
     * @param int $colorCount
     */
    public function setColorCount( $colorCount )
    {
        imagetruecolortopalette( $this->resource, true, $colorCount );
    }

    /**
     * Returns a copy of the image scaled to a rectangle with the given width 
     * and height according to the given aspectRatioMode and transformMode.
     * If either the width or the height is zero or negative, 
     * this function returns a null image.
     * 
     * @param int $width
     * @param int $height
     * @param AspectRatioMode $aspectRatioMode
     * @return MImage Description
     */
    public function scaled( $width, $height, $aspectRatioMode = AspectRatioMode::IGNORE_ASPECT_RATIO )
    {
        switch( $aspectRatioMode )
        {
            case AspectRatioMode::IGNORE_ASPECT_RATIO:
                break;
            case AspectRatioMode::KEEP_ASPECT_RATIO:
                $height = ( $width * $this->getHeight() ) / $this->getWidth();
                break;
            case AspectRatioMode::KEEP_ASPECT_RATIO_BY_EXPANDING:
                $width = ( $height * $this->getWidth() ) / $this->getHeight();
                break;
        }

        $newResource = imagecreatetruecolor( $width, $height );
        imagecopyresampled( $newResource, $this->resource, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight() );

        $newImage = MImage::fromResource( $newResource );
        $newImage->type = $this->type;

        return $newImage;
    }

    /**
     * Returns a scaled copy of the image. 
     * The returned image is scaled to the given height using the specified 
     * transformation mode.
     * 
     * @param int $height
     * @return MImage
     */
    public function scaledToHeight( $height )
    {
        return $this->scaled( 0, $height, AspectRatioMode::KEEP_ASPECT_RATIO_BY_EXPANDING );
    }

    /**
     * Returns a scaled copy of the image. 
     * The returned image is scaled to the given width using the specified transformation mode.
     * 
     * @param int $width
     * @return MImage
     */
    public function scaledToWidth( $width )
    {
        return $this->scaled( $width, 0, AspectRatioMode::KEEP_ASPECT_RATIO );
    }

    /**
     * Returns the size of the color table for the image.
     * 
     * @return int
     */
    public function colorCount()
    {
        return imagecolorstotal( $this->resource );
    }

    /**
     * Returns true if it is a null image, otherwise returns false.
     * A null image has all parameters set to zero and no allocated data.
     * 
     * @return boolean
     */
    public function isNull()
    {
        return ( $this->resource == null );
    }

    /**
     * Loads an image from the file with the given fileName. 
     * Returns true if the image was successfully loaded; otherwise returns false.
     * The loader attempts to read the image using the specified format, e.g., PNG or JPG.
     * 
     * @param string $fileName
     * @return boolean
     */
    public function load( $fileName )
    {
        $file = new MFileInfo( $fileName );
        $resource = false;
        $this->type = $file->getSuffix();

        if( $this->type == "png" )
        {
            $resource = imageCreateFromPng( $fileName );
            if( $resource )
            {
                imageAlphaBlending( $resource, true );
                imageSaveAlpha( $resource, true );
            }
        }
        else if( $this->type == "gif" )
        {
            $resource = imagecreatefromgif( $fileName );
        }
        else if( $this->type == "jpg" || $this->type == "jpeg" )
        {
            $resource = imagecreatefromjpeg( $fileName );
        }
        else
        {
            return false;
        }

        if( $resource === false )
        {
            return false;
        }

        $this->resource = $resource;
        $this->fileName = $fileName;
        $this->width = imagesx( $this->resource );
        $this->height = imagesy( $this->resource );

        return true;
    }

    /**
     * Returns the height of the image.
     * 
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * Returns the width of the image.
     * 
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Returns the format of the image.
     * 
     * @return string
     */
    public function getFormat()
    {
        return $this->type;
    }

    /**
     * Returns true if pos is a valid coordinate pair within the image; 
     * otherwise returns false.
     * 
     * @param int $x
     * @param int $y
     * @return boolean
     */
    public function valid( $x, $y )
    {
        return ( $x > 0 && $x < $this->getWidth() && $y > 0 && $y < $this->getHeight() );
    }

}
