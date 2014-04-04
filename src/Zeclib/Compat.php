<?php

/**
 * EC-CUBE が提供する API の不足や互換性を補う静的メソッドを集約したクラスです。
 *
 * @author Seiji Nitta
 */
class Zeclib_Compat
{
    private final function __construct() {
        throw new BadMethodCallException('Do not create instance.');
    }

    /**
     * 値を JSON 形式の文字列に変換します。
     *
     * @param mixed $native
     * @throws RuntimeException
     * @return string
     */
    public static function encodeJson($native)
    {
        if (function_exists('json_decode') && function_exists('json_last_error')) {
            $json = json_encode($native);
            $error = json_last_error();
            if ($error !== JSON_ERROR_NONE) {
                $message = function_exists('json_last_error_msg') ? json_last_error_msg() : 'error code: ' . $error;
                throw new RuntimeException($message, $error);
            }
            return $json;
        }

        $encoder = new Services_JSON();
        $json = $encoder->encode($native);
        if (Services_JSON::isError($json)) {
            throw new RuntimeException($json->toString(), $json->getCode());
        }

        return $json;
    }

    /**
     * JSON 形式の文字列を値に変換します。
     *
     * @param string $json
     * @param bool $return_assoc
     * @throws RuntimeException
     * @return mixed
     */
    public static function decodeJson($json, $return_assoc = false)
    {
        if (function_exists('json_decode') && function_exists('json_last_error')) {
            $native = json_decode($json, $return_assoc);
            $error = json_last_error();
            if ($error !== JSON_ERROR_NONE) {
                $message = function_exists('json_last_error_msg') ? json_last_error_msg() : 'error code: ' . $error;
                throw new RuntimeException($message, $error);
            }

            return $native;
        }

        // オートローダーを働かせて定数を定義させる。
        class_exists('Services_JSON');

        $options = $return_assoc ? SERVICES_JSON_LOOSE_TYPE : 0;
        $decoder = new Services_JSON($options);
        $native = $decoder->decode($json);
        if (Services_JSON::isError($json)) {
            throw new RuntimeException($native->toString(), $native->getCode());
        }

        return $native;
    }
}
