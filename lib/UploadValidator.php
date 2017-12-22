<?php

namespace WildWolf;

use Psr\Http\Message\UploadedFileInterface;

abstract class UploadValidator
{
    const ERROR_UPLOAD_FAILURE     = 1;
    const ERROR_FILE_TOO_SMALL     = 2;
    const ERROR_NOT_IMAGE          = 3;
    const ERROR_FILE_NOT_SUPPORTED = 4;
    const ERROR_FILE_TOO_BIG       = 5;

    public static function isUploadedFile(UploadedFileInterface $entry = null)
    {
        $fail = empty($entry) || UPLOAD_ERR_OK !== $entry->getError();
        if ($fail) {
            throw new ImageUploaderException('', self::ERROR_UPLOAD_FAILURE);
        }
    }

    public static function isValidSize(UploadedFileInterface $entry, int $min, int $max)
    {
        $size = $entry->getSize();
        if ($size < $min) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_SMALL);
        }

        if ($max && $size > $max) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_BIG);
        }
    }

    public static function isValidType(UploadedFileInterface $entry, array $accepted)
    {
        $fname = (string)$entry->getStream()->getMetadata('uri');
        $finfo = new \finfo(FILEINFO_MIME_TYPE, null);
        $type  = (string)$finfo->file($fname, FILEINFO_MIME_TYPE);
        if ('image/' !== substr($type, 0, strlen('image/'))) {
            throw new ImageUploaderException('The file is not an image.', self::ERROR_NOT_IMAGE);
        }

        if (!in_array($type, $accepted)) {
            throw new ImageUploaderException('File format is not supported / accepted.', self::ERROR_FILE_NOT_SUPPORTED);
        }
    }
}
