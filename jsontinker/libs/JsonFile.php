<?php

class JsonFile {
    private $filePath;

    public function __construct($filePath) {
        $this->filePath = $filePath;
    }

    public function read() {
        if (!file_exists($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Invalid JSON in file: ' . json_last_error_msg());
        }
        
        return $data;
    }

    public function write($data) {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            throw new RuntimeException('Failed to encode JSON: ' . json_last_error_msg());
        }
        
        if (file_put_contents($this->filePath, $json) === false) {
            throw new RuntimeException('Failed to write file: ' . $this->filePath);
        }
    }

    public function validate($data) {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data must be an array');
        }
        
        $json = json_encode($data);
        if ($json === false) {
            throw new InvalidArgumentException('Invalid JSON data: ' . json_last_error_msg());
        }
    }
}
