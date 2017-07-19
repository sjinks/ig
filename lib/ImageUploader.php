<?php

namespace WildWolf;

class ImageUploader
{
    const ERROR_UPLOAD_FAILURE     = 1;
    const ERROR_FILE_EMPTY         = 2;
    const ERROR_NOT_IMAGE          = 3;
    const ERROR_FILE_NOT_SUPPORTED = 4;
    const ERROR_FILE_TOO_BIG       = 5;
    const ERROR_GENERAL_FAILURE    = 6;

    /**
     * @var integer
     */
    private $max_upload_size = 5242880;

    /**
     * @var string
     */
    private $upload_dir;

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

    public function __construct()
    {
    }

    public function maxUploadSize() : int
    {
        return $this->max_upload_size;
    }

    public function setMaxUploadSize(int $v)
    {
        if ($v <= 0) {
            throw new \InvalidArgumentException();
        }

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

    public function directoryDepth() : integer
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

    private function basicSanityChecks(string $key)
    {
        if (empty($_FILES[$key]) || UPLOAD_ERR_OK !== $_FILES[$key]['error']) {
            throw ImageUploaderException('', self::ERROR_UPLOAD_FAILURE);
        }

        if (empty($_FILES[$key]['size'])) {
            throw ImageUploaderException('', self::ERROR_FILE_EMPTY);
        }

        if ($_FILES[$key]['size'] > $this->max_upload_size) {
            throw ImageUploaderException('', self::ERROR_FILE_TOO_BIG);
        }
    }

    private static function getFileType(string $file) : string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $type  = $finfo->file($file, FILEINFO_MIME_TYPE);
        return $type ? $type : 'application/octet-stream';
    }

    private function validateImageType(string $file) : string
    {
        if (!is_uploaded_file($file)) {
            throw ImageUploaderException('', self::ERROR_GENERAL_FAILURE);
        }

        $type = self::getFileType($file);
        if ('image/' !== substr($type, 0, strlen('image/'))) {
            throw ImageUploaderException('', self::ERROR_NOT_IMAGE);
        }

        if (!in_array($type, $this->accepted_types)) {
            throw ImageUploaderException('', self::ERROR_FILE_NOT_SUPPORTED);
        }

        return $type;
    }

    private function loadWithIMagick(string $name) : \IMagick
    {
        try {
            return new \Imagick($name);
        }
        catch (\ImagickException $e) {
            throw ImageUploaderException('', self::ERROR_FILE_NOT_SUPPORTED);
        }
    }

    private function loadWithGD(string $name, string $type)
    {
        $im = null;
        switch ($type) {
            case 'image/jpeg':
                $im = imagecreatefromjpeg($name);
                break;

            case 'image/png':
                $im = imagecreatefrompng($name);
                break;

            case 'image/gif':
                $im = imagecreatefromgif($name);
                break;
        }

        if (!is_resource($im)) {
            throw ImageUploaderException('', self::ERROR_FILE_NOT_SUPPORTED);
        }

        return $im;
    }

    private function tryLoadFile(string $name, string $type)
    {
        if (class_exists('imagick')) {
            return $this->loadWithIMagick($name);
        }

        if (extension_loaded('gd')) {
            return $this->loadWithGD($name, $type);
        }

        return null;
    }

    public function validateFile(string $key) : array
    {
        $this->basicSanityChecks($key);

        $fname = $_FILES[$key]['tmp_name'];
        $type  = $this->validateImageType($fname);
        $res   = $this->tryLoadFile($fname, $type);
        return [$res, $type];
    }

    private function getTargetDirectory(string $name) : string
    {
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

        $pos = strrpos($file, '.');
        if (false === $pos) {
            $name = $file;
            $ext  = '';
        }
        else {
            $name = substr($file, 0, $pos);
            $ext  = substr($file, $pos);
        }

        $suffix = 0;

        $f = Utils::safe_fopen($fullname, 'xb');
        while (false === $f) {
            ++$suffix;

            $fullname = $dir . DIRECTORY_SEPARATOR . $name . '-' . $suffix . $ext;
            $f        = Utils::safe_fopen($fullname, 'xb');
        }

        return [$f, $fullname];
    }

    private static function saveJpegWithGD($im, $f)
    {
        imagejpeg($im, $f);
    }

    private static function saveJpegWithIMagick(\IMagick $im, $f)
    {
        $im->setImageFormat('JPEG');
        $im->writeImageFile($f);
    }

    public function saveAsJpeg($r, string $name) : string
    {
        if (is_resource($r) && get_resource_type($r) !== 'gd' || is_object($r) && !($r instanceof \Imagick)) {
            throw new \InvalidArgumentException();
        }

        if (empty($this->upload_dir)) {
            throw new \RuntimeException("Upload directory is not set");
        }

        $name = basename($name);
        $dir  = $this->getTargetDirectory($name);

        $this->ensureDirectoryExists($dir);
        $res  = $this->createTargetFile($dir, $name);

        if (is_resource($r)) {
            self::saveJpegWithGD($r, $res[0]);
        }
        elseif ($r instanceof \Imagick) {
            self::saveJpegWithIMagick($r, $res[0]);
        }
        else {
            throw new ImageUploaderException('', self::ERROR_UPLOAD_FAILURE);
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
