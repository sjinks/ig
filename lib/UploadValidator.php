<?php

namespace WildWolf;

use WildWolf\ImageUploaderException;

abstract class UploadValidator
{
    const ERROR_UPLOAD_FAILURE     = 1;
    const ERROR_FILE_TOO_SMALL     = 2;
    const ERROR_FILE_TOO_BIG       = 5;

    public static function isUploadedFile(string $key)
    {
        $fail =    empty($_FILES[$key])
                || UPLOAD_ERR_OK !== $_FILES[$key]['error']
                || !is_uploaded_file($_FILES[$key]['tmp_name'])
        ;

        if ($fail) {
            throw new ImageUploaderException('', self::ERROR_UPLOAD_FAILURE);
        }
    }

    public static function isValidSize(string $key, int $min, int $max)
    {
        $size = $_FILES[$key]['size'];
        if ($size < $min) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_SMALL);
        }

        if ($size > $max) {
            throw new ImageUploaderException('', self::ERROR_FILE_TOO_BIG);
        }
    }
}
