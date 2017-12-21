<?php

namespace WildWolf;

class ImageUploader
{
    const ERROR_UPLOAD_FAILURE     = UploadValidator::ERROR_UPLOAD_FAILURE;
    const ERROR_FILE_TOO_SMALL     = UploadValidator::ERROR_FILE_TOO_SMALL;
    const ERROR_NOT_IMAGE          = 3;
    const ERROR_FILE_NOT_SUPPORTED = 4;
    const ERROR_FILE_TOO_BIG       = UploadValidator::ERROR_FILE_TOO_BIG;
    const ERROR_GENERAL_FAILURE    = 6;

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

    private $entry = null;

    public function __construct()
    {
    }

    public function setFile($entry)
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

    private function validateImageType(string $file) : string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE, null);
        $type  = (string)$finfo->file($file, FILEINFO_MIME_TYPE);
        if ('image/' !== substr($type, 0, strlen('image/'))) {
            throw new ImageUploaderException('The file is not an image.', self::ERROR_NOT_IMAGE);
        }

        if (!in_array($type, $this->accepted_types)) {
            throw new ImageUploaderException('File format is not supported / accepted.', self::ERROR_FILE_NOT_SUPPORTED);
        }

        return $type;
    }

    public function validateFile()
    {
        $entry = $this->entry;

        UploadValidator::isUploadedFile($entry);
        UploadValidator::isValidSize($entry, 0, $this->max_upload_size);

        $fname = $entry['tmp_name'];
        $this->validateImageType($fname);
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

    private function createFileForcefully(string $fullname) : array
    {
        $f = Utils::safe_fopen($fullname, 'wb');
        if (!is_resource($f)) {
            throw new ImageUploaderException("Failed to create the target file - " . $fullname, self::ERROR_UPLOAD_FAILURE);
        }

        return [$f, $fullname];
    }

    private function createTargetFile(string $dir, string $file)
    {
        $fullname = $dir . DIRECTORY_SEPARATOR . $file;
        if (!$this->check_uniquness) {
            return $this->createFileForcefully($fullname);
        }

        list($name, $ext) = Utils::splitFilename($file);

        $suffix = 0;

        $f = Utils::safe_fopen($fullname, 'xb');
        while (false === $f) {
            ++$suffix;

            $fullname = $dir . DIRECTORY_SEPARATOR . $name . '-' . $suffix . $ext;
            $f        = Utils::safe_fopen($fullname, 'xb');
        }

        return [$f, $fullname];
    }

    public function save(string $name) : string
    {
        $name = basename($name);
        $dir  = $this->getTargetDirectory($name);

        $this->ensureDirectoryExists($dir);
        $res  = $this->createTargetFile($dir, $name);
        $f1   = $res[0];

        try {
            $f0 = fopen($this->entry['tmp_name'], 'rb');

            if (!stream_copy_to_stream($f0, $f1)) {
                throw new ImageUploaderException("File copy failed.", self::ERROR_UPLOAD_FAILURE);
            }
        }
        finally {
            fclose($f1);
            if (is_resource($f0)) {
                fclose($f0);
            }
        }

        return $res[1];
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
