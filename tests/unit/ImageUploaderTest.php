<?php

namespace WildWolf;

class ImageUploaderTest extends \PHPUnit\Framework\TestCase
{
    public function testGettersSetters()
    {
        $uploader = new ImageUploader();

        $v1 = 100;
        $v2 = 200;
        $uploader->setMaxUploadSize($v1);
        $r  = $uploader->maxUploadSize();
        $this->assertEquals($v1, $r);
        $uploader->setMaxUploadSize($v2);
        $r  = $uploader->maxUploadSize();
        $this->assertEquals($v2, $r);

        $r  = $uploader->uploadDir();
        $this->assertEmpty($r);

        $v1 = '/tmp';
        $v2 = '/tmp/';
        $uploader->setUploadDir($v1);
        $r  = $uploader->uploadDir();
        $this->assertEquals($v1, $r);

        $uploader->setUploadDir($v2);
        $r  = $uploader->uploadDir();
        $this->assertEquals($v1 /* not a typo */, $r);

        $v1 = [];
        $v2 = ['image/jpeg'];
        $uploader->setAcceptedTypes($v1);
        $r  = $uploader->acceptedTypes();
        $this->assertEquals($v1, $r);
        $uploader->setAcceptedTypes($v2);
        $r  = $uploader->acceptedTypes();
        $this->assertEquals($v2, $r);

        $v1 = 1;
        $v2 = 2;
        $uploader->setDirectoryDepth($v1);
        $r  = $uploader->directoryDepth();
        $this->assertEquals($v1, $r);
        $uploader->setDirectoryDepth($v2);
        $r  = $uploader->directoryDepth();
        $this->assertEquals($v2, $r);

        $v1 = false;
        $v2 = true;
        $uploader->setCheckUniqueness($v1);
        $r  = $uploader->shouldCheckUniqueness();
        $this->assertEquals($v1, $r);
        $uploader->setCheckUniqueness($v2);
        $r  = $uploader->shouldCheckUniqueness();
        $this->assertEquals($v2, $r);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNegativeDirectoryDepth()
    {
        $uploader = new ImageUploader();
        $uploader->setDirectoryDepth(-1);
    }
}
