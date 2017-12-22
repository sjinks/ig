<?php

namespace WildWolf;

use Psr\Http\Message\UploadedFileInterface;

class ImageUploader
{
    const ERROR_UPLOAD_FAILURE     = UploadValidator::ERROR_UPLOAD_FAILURE;
    const ERROR_FILE_TOO_SMALL     = UploadValidator::ERROR_FILE_TOO_SMALL;
    const ERROR_NOT_IMAGE          = UploadValidator::ERROR_NOT_IMAGE;
    const ERROR_FILE_NOT_SUPPORTED = UploadValidator::ERROR_FILE_NOT_SUPPORTED;
    const ERROR_FILE_TOO_BIG       = UploadValidator::ERROR_FILE_TOO_BIG;

    /**
     * @var integer
     */
    private $max_upload_size = 7340032;

    /**
     * @var string
     */
    private $upload_dir = '';

    /**
     * @var array
     */
    private $accepted_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
    ];

    /**
     * @var integer
     */
    private $dir_depth = 3;

    /**
     * @var bool
     */
    private $check_uniquness = true;

    /**
     * @var UploadedFileInterface|null
     */
    private $entry = null;

    public function setFile(UploadedFileInterface $entry = null)
    {
        $this->entry = $entry;
    }

    public function maxUploadSize() : int
    {
        return $this->max_upload_size;
    }

    public function setMaxUploadSize(int $v)
    {
        $this->max_upload_size = $v;
    }

    public function uploadDir() : string
    {
        return $this->upload_dir;
    }

    public function setUploadDir(string $v)
    {
        $this->upload_dir = rtrim($v, '/\\');
    }

    public function acceptedTypes() : array
    {
        return $this->accepted_types;
    }

    public function setAcceptedTypes(array $v)
    {
        $this->accepted_types = $v;
    }

    public function directoryDepth() : int
    {
        return $this->dir_depth;
    }

    public function setDirectoryDepth(int $v)
    {
        if ($v < 0) {
            throw new \InvalidArgumentException();
        }

        $this->dir_depth = $v;
    }

    public function shouldCheckUniqueness() : bool
    {
        return $this->check_uniquness;
    }

    public function setCheckUniqueness(bool $v)
    {
        $this->check_uniquness = $v;
    }

    public function validateFile()
    {
        $entry = $this->entry;

        UploadValidator::isUploadedFile($entry);
        UploadValidator::isValidSize($entry, 0, $this->max_upload_size);
        UploadValidator::isValidType($entry, $this->accepted_types);
    }

    private function getTargetDirectory(string $name) : string
    {
        if (empty($this->upload_dir)) {
            throw new \RuntimeException("Upload directory is not set");
        }

        $parts = [$this->upload_dir];
        for ($i=0; $i<$this->dir_depth; ++$i) {
            $part = substr($name, 2*$i, 2);
            if ($part) {
                $parts[] = $part;
            }
            else {
                break;
            }
        }

        return join(DIRECTORY_SEPARATOR, $parts);
    }

    private function ensureDirectoryExists($dir)
    {
        if (!is_dir($dir) && false === mkdir($dir, 0755, true)) {
            throw new ImageUploaderException("Failed to create target directory", self::ERROR_UPLOAD_FAILURE);
        }
    }

    /**
     * @param string $dir
     * @param string $file
     * @return string
     */
    private function createTargetFile(string $dir, string $file) : string
    {
        $fullname = $dir . DIRECTORY_SEPARATOR . $file;

        if ($this->check_uniquness) {
            list($name, $ext) = Utils::splitFilename($file);

            $suffix = 0;

            $f = Utils::safe_fopen($fullname, 'xb');
            while (false === $f) {
                ++$suffix;

                $fullname = $dir . DIRECTORY_SEPARATOR . $name . '-' . $suffix . $ext;
                $f        = Utils::safe_fopen($fullname, 'xb');
            }

            fclose($f);
        }

        return $fullname;
    }

    public function save(string $name) : string
    {
        $name = basename($name);
        $dir  = $this->getTargetDirectory($name);

        $this->ensureDirectoryExists($dir);
        $dest = $this->createTargetFile($dir, $name);

        try {
            $this->entry->moveTo($dest);
        }
        catch (\RuntimeException $e) {
            throw new ImageUploaderException($e->getMessage(), self::ERROR_UPLOAD_FAILURE);
        }

        return $dest;
    }

    public function getTargetName(string $name, bool $relative = true) : string
    {
        $name = basename($name);
        $dir  = $this->getTargetDirectory($name);

        if ($relative) {
            if ($dir === $this->upload_dir) {
                return $name;
            }

            if (($this->upload_dir . DIRECTORY_SEPARATOR) === substr($dir, 0, strlen($this->upload_dir) + 1)) {
                $dir = substr($dir, strlen($this->upload_dir) + 1);
            }
        }

        return $dir . DIRECTORY_SEPARATOR . $name;
    }
}
