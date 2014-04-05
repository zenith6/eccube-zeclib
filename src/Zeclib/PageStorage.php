<?php

/**
 * @author Seiji Nitta
 */
class Zeclib_PageStorage extends ArrayObject
{
    protected $secretKey;

    /**
     * @param array $input
     * @param string $secretKey
     */

    public function __construct($input = array(), $secretKey = null)
    {
        parent::__construct($input);

        $this->secretKey = $secretKey;
    }

    /**
     * @return string
     */

    public function __toString()
    {
        return $this->encode();
    }

    /**
     * @param string $data
     * @param string $secretKey
     * @return string
     */

    protected function hash($data, $secretKey)
    {
        return hash_hmac('sha1', $data, $secretKey);
    }

    /**
     * @return string
     */

    public function encode()
    {
        $data = iterator_to_array($this);
        $serialized = serialize($data);
        $hash = $this->hash($serialized, $this->secretKey);
        return base64_encode($serialized) . '--' . base64_encode($hash);
    }

    /**
     * @param string $encoded
     * @throws RuntimeException
     * @return array
     */

    public function decode($encoded)
    {
        @list($serialized, $hash) = array_map('base64_decode', (array)explode('--', $encoded, 2));

        $current_hash = $this->hash($serialized, $this->secretKey);
        if ($hash !== $current_hash) {
            throw new RuntimeException('Failed to decode. secret key mismatch.');
        }

        $data = unserialize($serialized);
        if (!is_array($data)) {
            throw new RuntimeException('Failed to decode. serialized data is broken.');
        }

        return $data;
    }

    /**
     * @param string $encoded
     */

    public function restore($encoded)
    {
        $data = $this->decode($encoded);
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }
    }
}
