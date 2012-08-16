<?php

use SilverStripe\Framework\Core\Application;

/**
 * @package framework
 * @subpackage tests
 */
class ImageTest extends SapphireTest {
	
	static $fixture_file = 'ImageTest.yml';

	function setUp() {
		parent::setUp();

		// Create a test folders for each of the fixture references
		$public = Application::curr()->getPublicPath();
		$folderIDs = $this->allFixtureIDs('Folder');

		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);

			if(!file_exists($public."/$folder->Filename")) mkdir($public."/$folder->Filename");
		}

		// Create a test files for each of the fixture references
		$fileIDs = $this->allFixtureIDs('Image');
		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			$image = imagecreatetruecolor(300,300);

			imagepng($image, $public."/$file->Filename");
			imagedestroy($image);

			$file->write();
		}
	}

	function testGetTagWithTitle() {
		$image = $this->objFromFixture('Image', 'imageWithTitle');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test_image.png" alt="This is a image Title" />';
		$actual = $image->getTag();

		$this->assertEquals($expected, $actual);
	}

	function testGetTagWithoutTitle() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test_image.png" alt="test_image" />';
		$actual = $image->getTag();

		$this->assertEquals($expected, $actual);
	}

	function testGetTagWithoutTitleContainingDots() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitleContainingDots');
		$expected = '<img src="' . Director::baseUrl() . 'assets/ImageTest/test.image.with.dots.png" alt="test.image.with.dots" />';
		$actual = $image->getTag();

		$this->assertEquals($expected, $actual);
	}

	function tearDown() {
		/* Remove the test files that we've created */
		$fileIDs = $this->allFixtureIDs('Image');
		$public = Application::curr()->getPublicPath();

		foreach($fileIDs as $fileID) {
			$file = DataObject::get_by_id('Image', $fileID);
			if($file && file_exists($public."/$file->Filename")) unlink($public."/$file->Filename");
		}

		/* Remove the test folders that we've crated */
		$folderIDs = $this->allFixtureIDs('Folder');
		foreach($folderIDs as $folderID) {
			$folder = DataObject::get_by_id('Folder', $folderID);
			if($folder && file_exists($public."/$folder->Filename")) Filesystem::removeFolder($public."/$folder->Filename");
		}

		parent::tearDown();
	}

	function testMultipleGenerateManipulationCalls() {
		$image = $this->objFromFixture('Image', 'imageWithoutTitle');

		$imageFirst = $image->SetWidth(200);
		$this->assertNotNull($imageFirst);
		$expected = 200;
		$actual = $imageFirst->getWidth();

		$this->assertEquals($expected, $actual);

		$imageSecond = $imageFirst->setHeight(100);
		$this->assertNotNull($imageSecond);
		$expected = 100;
		$actual = $imageSecond->getHeight();
		$this->assertEquals($expected, $actual);
	}
	
	function testGeneratedImageDeletion() {
		$image = $this->objFromFixture('Image', 'imageWithMetacharacters');
		$image_generated = $image->SetWidth(200);
		$p = $image_generated->getFullPath();
		$this->assertTrue(file_exists($p));
		$image->deleteFormattedImages();
		$this->assertFalse(file_exists($p));
	}
}
