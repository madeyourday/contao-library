<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Library
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\Test;

use ReflectionClass;
use Contao\Image;

/**
 * @runTestsInSeparateProcesses
 */
class ImageTest extends \PHPUnit_Framework_TestCase
{
    var $tempDirectory;

    protected function setUp()
    {
        $this->tempDirectory = __DIR__ . '/../tmp';
        mkdir($this->tempDirectory);
        mkdir($this->tempDirectory . '/assets');
        mkdir($this->tempDirectory . '/assets/images');
        foreach ([0,1,2,3,4,5,6,7,8,9,'a','b','c','d','e','f'] as $subdir) {
            mkdir($this->tempDirectory . '/assets/images/' . $subdir);
        }

        copy(__DIR__ . '/../Fixtures/dummy.jpg', $this->tempDirectory . '/dummy.jpg');

        eval('class SystemTest extends Contao\System
        {
            public static function log($strText, $strFunction, $strCategory) {}
        }');


        $GLOBALS['TL_CONFIG']['gdMaxImgWidth']  = 3000;
        $GLOBALS['TL_CONFIG']['gdMaxImgHeight'] = 3000;
        $GLOBALS['TL_CONFIG']['validImageTypes'] = 'jpeg,jpg';
        class_alias('Contao\File', 'File');
        class_alias('Contao\Files', 'Files');
        class_alias('SystemTest', 'System');
        class_alias('Contao\Config', 'Config');
        define('TL_ERROR', 'ERROR');
        define('TL_ROOT', $this->tempDirectory);

        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        // Delete temp directory
        exec('rm -rf ' . escapeshellarg($this->tempDirectory));
    }

    public function testConstruct()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                }
            }
        ));

        $this->assertInstanceOf('Contao\Image', new Image($fileMock));
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testConstructWithInexistentFile()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(false));

        new Image($fileMock);
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testConstructWithInvalidExtension()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'foobar';
                }
            }
        ));

        new Image($fileMock);
    }

    public function testGetDeprecatedInvalidImages()
    {
        $this->assertNull(Image::get('', 100, 100));
        $this->assertNull(Image::get(0, 100, 100));
        $this->assertNull(Image::get(null, 100, 100));
    }

    /**
     * @dataProvider getComputeResizeDataWithoutImportantPart
     */
    public function testComputeResizeWithoutImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('File')
                    ->setMethods(array('__get', 'exists'))
                    ->setConstructorArgs(array('dummy.jpg'))
                    ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) use($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
                }
            }
        ));


        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize()
        );

        $imageObj->setZoomLevel(50);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 50 should return the same results if no important part is specified'
        );

        $imageObj->setZoomLevel(100);

        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize(),
            'Zoom 100 should return the same results if no important part is specified'
        );
    }

    public function getComputeResizeDataWithoutImportantPart()
    {
        return [

            'No dimensions' =>
            [[null, null, 100, 100, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Same dimensions' =>
            [[100, 100, 100, 100, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Scale down' =>
            [[50, 50, 100, 100, null], [
                'width' => 50,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 50,
            ]],

            'Scale up' =>
            [[100, 100, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only' =>
            [[100, null, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Height only' =>
            [[null, 100, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Crop landscape' =>
            [[100, 50, 100, 100, null], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => -25,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Crop portrait' =>
            [[50, 100, 100, 100, null], [
                'width' => 50,
                'height' => 100,
                'target_x' => -25,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Mode proportional landscape' =>
            [[100, 10, 100, 50, 'proportional'], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ]],

            'Mode proportional portrait' =>
            [[10, 100, 50, 100, 'proportional'], [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ]],

            'Mode proportional square' =>
            [[100, 50, 100, 100, 'proportional'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Mode box landscape 1' =>
            [[100, 100, 100, 50, 'box'], [
                'width' => 100,
                'height' => 50,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 50,
            ]],

            'Mode box landscape 2' =>
            [[100, 10, 100, 50, 'box'], [
                'width' => 20,
                'height' => 10,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 20,
                'target_height' => 10,
            ]],

            'Mode box portrait 1' =>
            [[100, 100, 50, 100, 'box'], [
                'width' => 50,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 50,
                'target_height' => 100,
            ]],

            'Mode box portrait 2' =>
            [[10, 100, 50, 100, 'box'], [
                'width' => 10,
                'height' => 20,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 10,
                'target_height' => 20,
            ]],

            'Mode left_top landscape' =>
            [[100, 100, 100, 50, 'left_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_top portrait' =>
            [[100, 100, 50, 100, 'left_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_top landscape' =>
            [[100, 100, 100, 50, 'center_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_top portrait' =>
            [[100, 100, 50, 100, 'center_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_top landscape' =>
            [[100, 100, 100, 50, 'right_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_top portrait' =>
            [[100, 100, 50, 100, 'right_top'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode left_center landscape' =>
            [[100, 100, 100, 50, 'left_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_center portrait' =>
            [[100, 100, 50, 100, 'left_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_center landscape' =>
            [[100, 100, 100, 50, 'center_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_center portrait' =>
            [[100, 100, 50, 100, 'center_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_center landscape' =>
            [[100, 100, 100, 50, 'right_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_center portrait' =>
            [[100, 100, 50, 100, 'right_center'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -50,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode left_bottom landscape' =>
            [[100, 100, 100, 50, 'left_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode left_bottom portrait' =>
            [[100, 100, 50, 100, 'left_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode center_bottom landscape' =>
            [[100, 100, 100, 50, 'center_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode center_bottom portrait' =>
            [[100, 100, 50, 100, 'center_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Mode right_bottom landscape' =>
            [[100, 100, 100, 50, 'right_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => -100,
                'target_y' => 0,
                'target_width' => 200,
                'target_height' => 100,
            ]],

            'Mode right_bottom portrait' =>
            [[100, 100, 50, 100, 'right_bottom'], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => -100,
                'target_width' => 100,
                'target_height' => 200,
            ]],

            'Float values' =>
            [[100.4, 100.4, 50, 50, null], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],
        ];
    }

    /**
     * @dataProvider getComputeResizeDataWithImportantPart
     */
    public function testComputeResizeWithImportantPart($arguments, $expectedResult)
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) use($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return $arguments[2];
                    case 'height':
                        return $arguments[3];
                }
            }
        ));


        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[4]);
        $imageObj->setZoomLevel($arguments[5]);
        $imageObj->setImportantPart($arguments[6]);


        $this->assertEquals(
            $expectedResult,
            $imageObj->computeResize()
        );
    }

    public function getComputeResizeDataWithImportantPart()
    {
        return [

            'No dimensions zoom 0' =>
            [[null, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'No dimensions zoom 50' =>
            [[null, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 80,
                'height' => 80,
                'target_x' => -10,
                'target_y' => -10,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'No dimensions zoom 100' =>
            [[null, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 60,
                'height' => 60,
                'target_x' => -20,
                'target_y' => -20,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only zoom 0' =>
            [[100, null, 100, 100, null, 0, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Width only zoom 50' =>
            [[100, null, 100, 100, null, 50, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -13,
                'target_y' => -13,
                'target_width' => 125,
                'target_height' => 125,
            ]],

            'Width only zoom 100' =>
            [[100, null, 100, 100, null, 100, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -33,
                'target_y' => -33,
                'target_width' => 167,
                'target_height' => 167,
            ]],

            'Same dimensions zoom 0' =>
            [[100, 100, 100, 100, null, 0, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => 0,
                'target_y' => 0,
                'target_width' => 100,
                'target_height' => 100,
            ]],

            'Same dimensions zoom 50' =>
            [[100, 100, 100, 100, null, 50, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -17,
                'target_y' => -17,
                'target_width' => 133,
                'target_height' => 133,
            ]],

            'Same dimensions zoom 100' =>
            [[100, 100, 100, 100, null, 100, ['x' => 25, 'y' => 25, 'width' => 50, 'height' => 50]], [
                'width' => 100,
                'height' => 100,
                'target_x' => -50,
                'target_y' => -50,
                'target_width' => 200,
                'target_height' => 200,
            ]],

            'Landscape to portrait zoom 0' =>
            [[100, 200, 200, 100, null, 0, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -233,
                'target_y' => 0,
                'target_width' => 400,
                'target_height' => 200,
            ]],

            'Landscape to portrait zoom 50' =>
            [[100, 200, 200, 100, null, 50, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -367,
                'target_y' => -43,
                'target_width' => 571,
                'target_height' => 286,
            ]],

            'Landscape to portrait zoom 100' =>
            [[100, 200, 200, 100, null, 100, ['x' => 140, 'y' => 40, 'width' => 20, 'height' => 20]], [
                'width' => 100,
                'height' => 200,
                'target_x' => -700,
                'target_y' => -150,
                'target_width' => 1000,
                'target_height' => 500,
            ]]

        ];
    }

    public function testCreateGdImage()
    {
        $image = Image::createGdImage(100, 100);

        $this->assertInternalType('resource', $image);
        $this->assertTrue(imageistruecolor($image));
        $this->assertEquals(100, imagesx($image));
        $this->assertEquals(100, imagesy($image));
        $this->assertEquals(127, imagecolorsforindex($image, imagecolorat($image, 0, 0))["alpha"], 'Image should be transparent');
        $this->assertEquals(127, imagecolorsforindex($image, imagecolorat($image, 99, 99))["alpha"], 'Image should be transparent');
    }

    public function testGetGdImageFromFile()
    {
        foreach (['gif', 'jpeg', 'png'] as $type) {
            $image = imagecreatetruecolor(100, 100);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));
            $method = 'image' . $type;
            $method($image, $this->tempDirectory . '/test.' . $type);
            imagedestroy($image);

            $image = Image::getGdImageFromFile(new \File('test.' . $type));

            $this->assertInternalType('resource', $image);
            $this->assertEquals(100, imagesx($image));
            $this->assertEquals(100, imagesy($image));
        }
    }

    public function testSaveGdImageToFile()
    {
        foreach (['gif', 'jpeg', 'png'] as $type) {
            $file = $this->tempDirectory . '/test.' . $type;
            $image = imagecreatetruecolor(100, 100);
            imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

            Image::saveGdImageToFile($image, $file, $type);

            $this->assertFileExists($file);

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $this->assertEquals('image/' . $type, $finfo->file($file));
        }
    }

    public function testConvertGdImageToPaletteImage()
    {
        $image = imagecreatetruecolor(100, 100);

        // Whole image black
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        // Bottom right quater transparent
        imagealphablending($image, false);
        imagefilledrectangle($image, 50, 50, 100, 100, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $image = Image::convertGdImageToPaletteImage($image);

        $this->assertInternalType('resource', $image);
        $this->assertFalse(imageistruecolor($image));
        $this->assertEquals(
            ['red' => 0, 'green' => 0, 'blue' => 0, 'alpha' => 0],
            imagecolorsforindex($image, imagecolorat($image, 0, 0)),
            'Left top pixel should be black'
        );
        $this->assertEquals(
            127,
            imagecolorsforindex($image, imagecolorat($image, 75, 75))["alpha"],
            'Bottom right quater should be transparent'
        );
    }

    public function testCountGdImageColors()
    {
        $image = imagecreatetruecolor(100, 100);
        imagealphablending($image, false);

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 0, 0, 0));
        imagefilledrectangle($image, 50, 0, 100, 50, imagecolorallocatealpha($image, 0, 255, 0, 0));
        imagefilledrectangle($image, 0, 50, 50, 100, imagecolorallocatealpha($image, 0, 0, 255, 0));
        imagefilledrectangle($image, 50, 50, 100, 100, imagecolorallocatealpha($image, 0, 0, 0, 127));

        $this->assertEquals(4, Image::countGdImageColors($image));
        $this->assertEquals(4, Image::countGdImageColors($image, 256));
        $this->assertEquals(2, Image::countGdImageColors($image, 1));
    }

    public function testIsGdImageSemitransparent()
    {
        $image = imagecreatetruecolor(100, 100);
        imagealphablending($image, false);

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));

        $this->assertFalse(Image::isGdImageSemitransparent($image));

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        $this->assertFalse(Image::isGdImageSemitransparent($image));

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 126));
        $this->assertTrue(Image::isGdImageSemitransparent($image));

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 1));
        $this->assertTrue(Image::isGdImageSemitransparent($image));

        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 0));
        $this->assertFalse(Image::isGdImageSemitransparent($image));
    }

    public function testSettersAndGetters()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return 'dummy.jpg';
                    case 'width':
                        return 100;
                    case 'height':
                        return 100;
                }
            }
        ));

        $imageObj = new Image($fileMock);

        $this->assertFalse($imageObj->getForceOverride());
        $imageObj->setForceOverride(true);
        $this->assertTrue($imageObj->getForceOverride());

        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 0,
            'y' => 0,
            'width' => 100,
            'height' => 100
        ]);
        $imageObj->setImportantPart([
            'x' => 20,
            'y' => 40,
            'width' => 80,
            'height' => 120
        ]);
        $this->assertSame($imageObj->getImportantPart(), [
            'x' => 20,
            'y' => 40,
            'width' => 80,
            'height' => 120
        ]);

        $this->assertSame($imageObj->getTargetHeight(), 0);
        $imageObj->setTargetHeight(20);
        $this->assertSame($imageObj->getTargetHeight(), 20);
        $imageObj->setTargetHeight(50.125);
        $this->assertSame($imageObj->getTargetHeight(), 50);

        $this->assertSame($imageObj->getTargetWidth(), 0);
        $imageObj->setTargetWidth(20);
        $this->assertSame($imageObj->getTargetWidth(), 20);
        $imageObj->setTargetWidth(50.125);
        $this->assertSame($imageObj->getTargetWidth(), 50);

        $this->assertSame($imageObj->getTargetPath(), '');
        $imageObj->setTargetPath('foobar');
        $this->assertSame($imageObj->getTargetPath(), 'foobar');

        $this->assertSame($imageObj->getZoomLevel(), 0);
        $imageObj->setZoomLevel(54);
        $this->assertSame($imageObj->getZoomLevel(), 54);

        $this->assertSame($imageObj->getResizeMode(), 'crop');
        $imageObj->setResizeMode('foobar');
        $this->assertSame($imageObj->getResizeMode(), 'foobar');

        $this->assertSame($imageObj->getOriginalPath(), 'dummy.jpg');
        $this->assertSame($imageObj->getResizedPath(), '');
    }

    /**
     * @dataProvider getCacheName
     */
    public function testGetCacheName($arguments, $expectedCacheName)
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) use($arguments) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                    case 'path':
                        return $arguments[2];
                    case 'filename':
                        return $arguments[2];
                    case 'mtime':
                        return $arguments[5];
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setTargetWidth($arguments[0]);
        $imageObj->setTargetHeight($arguments[1]);
        $imageObj->setResizeMode($arguments[3]);
        $imageObj->setZoomLevel($arguments[4]);
        $imageObj->setImportantPart($arguments[6]);

        $this->assertSame($imageObj->getCacheName(), $expectedCacheName);
    }

    public function getCacheName()
    {
        // target width, target height, file name (path), resize mode, zoom level, mtime, important part
        // expected cache name
        return [
                [
                    [100, 100, 'dummy.jpg', 'crop', 0, 12345678, ['x' => 20, 'y' => 20, 'width' => 60, 'height' => 60]],
                    'assets/images/9/dummy.jpg-fd9db329.jpg'
                ],

                [
                    [200, 100, 'test.jpg', 'proportional', 50, 87654321, ['x' => 30, 'y' => 20, 'width' => 60, 'height' => 90]],
                    'assets/images/b/test.jpg-9c8f00bb.jpg'
                ],

                [
                    [100, 200, 'other.jpg', 'center_center', 100, 6666666, ['x' => 10, 'y' => 20, 'width' => 70, 'height' => 20]],
                    'assets/images/2/other.jpg-5709a132.jpg'
                ]
            ];
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsNegative()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(-1);
    }

    /**
     * @expectedException   InvalidArgumentException
     */
    public function testSetZoomOutOfBoundsPositive()
    {
        $fileMock = $this->getMockBuilder('File')
            ->setMethods(array('__get', 'exists'))
            ->setConstructorArgs(array('dummy.jpg'))
            ->getMock();
        $fileMock->expects($this->any())->method('exists')->will($this->returnValue(true));
        $fileMock->expects($this->any())->method('__get')->will($this->returnCallback(
            function($key) {
                switch ($key) {
                    case 'extension':
                        return 'jpg';
                }
            }
        ));

        $imageObj = new Image($fileMock);
        $imageObj->setZoomLevel(101);
    }

    /**
     * @dataProvider getLegacyGet
     */
    public function testLegacyGet($arguments, $expectedResult)
    {
        $result = Image::get(
            $arguments[0],
            $arguments[1],
            $arguments[2],
            $arguments[3],
            $arguments[4],
            $arguments[5]
        );

        $this->assertSame($result, $expectedResult);
    }

    public function getLegacyGet()
    {
        // original image, target width, target height, resize mode, target, force override
        // expected result

        return [

            'No empty image path returns null' =>
                [
                    ['', 100, 100, 'crop', null, false],
                    null
                ],

            'Inexistent file returns null' =>
                [
                    ['foobar.jpg', 100, 100, 'crop', null, false],
                    null
                ],

            'No resize necessary returns same path' =>
                [
                    ['dummy.jpg', 200, 200, 'crop', null, false],
                    'dummy.jpg'
                ]
        ];
    }
}
