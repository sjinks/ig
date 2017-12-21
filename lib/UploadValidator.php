<?php

namespace WildWolf;

abstract class UploadValidator
{
    const ERROR_UPLOAD_FAILURE = 1;
    const ERROR_FILE_TOO_SMALL = 2;
    const ERROR_FILE_TOO_BIG   = 5;

    public static function isUploadedFile(array $entry = null)
    {
        $fail =    empty($entry)
                || UPLOAD_ERR_OK !== $entry['error']
                || !is_uploaded_file($entry['tmp_name'])
        ;

        if ($fail) {
            throw new ImageUploaderException('', self::ERROR_UPLOAD_FAILURE);
        }
    }

    public static function isValidSize(array $entry, int $min, int $max)
    {
        $size = $entry['size'];
        if ($size < $min) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_SMALL);
        }

        if ($max && $size > $max) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_BIG);
        }
    }
}
