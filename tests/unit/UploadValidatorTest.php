<?php

namespace WildWolf {

    function is_uploaded_file($filename)
    {
        return true;
    }

}

namespace WildWolf\Test {

    use WildWolf\UploadValidator;
    use WildWolf\ImageUploaderException;

    class UploadValidatorTest extends \PHPUnit\Framework\TestCase
    {
        public function testEmptyFiles()
        {
            $_FILES = [];
            $this->expectException(ImageUploaderException::class);
            $this->expectExceptionCode(UploadValidator::ERROR_UPLOAD_FAILURE);
            UploadValidator::isUploadedFile('file');
        }

        public function testUploadError()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_NO_FILE]];
            $this->expectException(ImageUploaderException::class);
            $this->expectExceptionCode(UploadValidator::ERROR_UPLOAD_FAILURE);
            UploadValidator::isUploadedFile('file');
        }

        public function testNormalUpload()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            UploadValidator::isUploadedFile('file');
            $this->assertTrue(true);
        }

        public function testSmallFile()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            $this->expectException(ImageUploaderException::class);
            $this->expectExceptionCode(UploadValidator::ERROR_FILE_TOO_SMALL);
            UploadValidator::isValidSize('file', 101, 102);
        }

        public function testLargeFile()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            $this->expectException(ImageUploaderException::class);
            $this->expectExceptionCode(UploadValidator::ERROR_FILE_TOO_BIG);
            UploadValidator::isValidSize('file', 0, 10);
        }

        public function testNoSizeLimit()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            UploadValidator::isValidSize('file', 0, 0);
            $this->assertTrue(true);
        }

        public function testAllowEmptyFile()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            UploadValidator::isValidSize('file', -1, 0);
            $this->assertTrue(true);
        }

        public function testNormalFile()
        {
            $_FILES = ['file' => ['name' => 'file.jpeg', 'type' => 'image/jpeg', 'size' => 100, 'tmp_name' => 'phptmp', 'error' => UPLOAD_ERR_OK]];
            UploadValidator::isValidSize('file', 0, 1000);
            $this->assertTrue(true);
        }
    }
}
