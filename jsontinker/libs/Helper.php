<?php

class Helper {

    /**
     * Process form data using dot notation keys.
     * Keys use dots for nested access, e.g. "people.0.addresses.0.zip"
     * Numeric segments are treated as array indices.
     */
    public function processFormData($data) {
        $result = [];

        foreach ($data as $key => $value) {
            // Skip empty keys
            if ($key === '') {
                continue;
            }

            // Convert value to appropriate type
            $processedValue = $value;
            if (is_string($value) && is_numeric($value)) {
                // Check if it's an integer or float
                if (strpos($value, '.') !== false) {
                    $processedValue = (float) $value;
                } else {
                    $processedValue = (int) $value;
                }
            } elseif (is_string($value) && strtolower($value) === 'true') {
                $processedValue = true;
            } elseif (is_string($value) && strtolower($value) === 'false') {
                $processedValue = false;
            } elseif (is_string($value) && strtolower($value) === 'null') {
                $processedValue = null;
            }

            // Split key by dots to create nested structure.
            // Numeric segments are treated as array indices.
            $keys = explode('.', $key);
            $current = &$result;

            foreach ($keys as $i => $part) {
                $isNumericIndex = ctype_digit($part) || (is_numeric($part) && $part === (string)(int)$part);

                if ($isNumericIndex) {
                    $index = (int)$part;

                    // Current must be an array (list)
                    if (!is_array($current)) {
                        $current = [];
                    }

                    // Last key? Set the value
                    if ($i === count($keys) - 1) {
                        $current[$index] = $processedValue;
                    } else {
                        // Initialize next level if needed
                        if (!isset($current[$index])) {
                            $current[$index] = [];
                        }
                        $current = &$current[$index];
                    }
                } else {
                    // Last key? Set the value
                    if ($i === count($keys) - 1) {
                        $current[$part] = $processedValue;
                    } else {
                        // Initialize next level if needed
                        if (!isset($current[$part])) {
                            $current[$part] = [];
                        }
                        $current = &$current[$part];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Render form fields recursively for JSON data using dot notation
     */
    public function renderFormFields($data, $parentKey = '') {
        $html = '';

        foreach ($data as $key => $value) {
            $fieldName = $parentKey ? $parentKey . '.' . $key : $key;

            if (is_array($value)) {
                // Check if this is an indexed array (list) or associative array (object)
                $isList = array_keys($value) === range(0, count($value) - 1);

                if ($isList) {
                    // Render array items
                    $html .= '<div class="nested-section">';
                    $html .= '<div class="section-header"><strong>' . $this->createTitle($key) . '</strong> (Array)</div>';
                    $html .= '<div class="nested-content">';
                    $html .= '<div class="array-container" data-array-name="' . htmlspecialchars($fieldName) . '" data-array-length="' . count($value) . '">';

                    foreach ($value as $index => $item) {
                        $itemFieldName = $fieldName . '.' . $index;
                        if (is_array($item)) {
                            $html .= '<div class="nested-section array-item" data-index="' . $index . '">';
                            $html .= '<div class="section-header"><strong>Group</strong> ' . $index . ' <button type="button" class="btn-remove-item" onclick="removeArrayItem(this)">Remove</button></div>';
                            $html .= '<div class="nested-content">';
                            $html .= $this->renderFormFields($item, $itemFieldName);
                            $html .= '</div>';
                            $html .= '</div>';
                        } else {
                            $html .= '<div class="form-group array-item" data-index="' . $index . '">';
                            // $html .= '<label for="' . htmlspecialchars($itemFieldName) . '">' . htmlspecialchars($key) . '[' . $index . ']</label>';
                            $html .= '<div class="field-with-actions">';

                            if (is_bool($item)) {
                                // Boolean: checkbox with hidden input for false value
                                $html .= '<input type="hidden" name="data[' . htmlspecialchars($itemFieldName) . ']" value="false">';
                                $html .= '<input type="checkbox" name="data[' . htmlspecialchars($itemFieldName) . ']" id="' . htmlspecialchars($itemFieldName) . '" value="true"' . ($item ? ' checked' : '') . '>';
                            } elseif (is_int($item) || is_float($item)) {
                                // Number: input type number
                                $html .= '<input type="number" name="data[' . htmlspecialchars($itemFieldName) . ']" id="' . htmlspecialchars($itemFieldName) . '" value="' . htmlspecialchars($item) . '" step="any">';
                            } else {
                                // String or null: textarea
                                $html .= '<textarea name="data[' . htmlspecialchars($itemFieldName) . ']" id="' . htmlspecialchars($itemFieldName) . '">' . htmlspecialchars($item) . '</textarea>';
                            }

                            $html .= '<button type="button" class="btn-remove-item" onclick="removeArrayItem(this)">Remove</button>';
                            $html .= '</div>';
                            $html .= '<div class="error-message"></div>';
                            $html .= '</div>';
                        }
                    }

                    $html .= '</div>'; // close array-container
                    $html .= '<button type="button" class="btn-add-item" onclick="addArrayItem(this)">Add Item</button>';
                    $html .= '</div>'; // close nested-content
                    $html .= '</div>'; // close nested-section
                } else {
                    // Render object (associative array)
                    $html .= '<div class="nested-section">';
                    $html .= '<div class="section-header"><strong>' . $this->createTitle($key) . '</strong></div>';
                    $html .= '<div class="nested-content">';
                    $html .= $this->renderFormFields($value, $fieldName);
                    $html .= '</div>';
                    $html .= '</div>';
                }
            } else {
                // Render simple field with type detection
                $html .= '<div class="form-group">';
                $html .= '<label for="' . htmlspecialchars($fieldName) . '">' . htmlspecialchars($key) . '</label>';

                if (is_bool($value)) {
                    // Boolean: checkbox with hidden input for false value
                    $html .= '<input type="hidden" name="data[' . htmlspecialchars($fieldName) . ']" value="false">';
                    $html .= '<input type="checkbox" name="data[' . htmlspecialchars($fieldName) . ']" id="' . htmlspecialchars($fieldName) . '" value="true"' . ($value ? ' checked' : '') . '>';
                } elseif (is_int($value) || is_float($value)) {
                    // Number: input type number
                    $html .= '<input type="number" name="data[' . htmlspecialchars($fieldName) . ']" id="' . htmlspecialchars($fieldName) . '" value="' . htmlspecialchars($value) . '" step="any">';
                } else {
                    // String or null: textarea
                    $html .= '<textarea name="data[' . htmlspecialchars($fieldName) . ']" id="' . htmlspecialchars($fieldName) . '">' . htmlspecialchars($value) . '</textarea>';
                }

                $html .= '<div class="error-message"></div>';
                $html .= '</div>';
            }
        }

        return $html;
    }

    public function createTitle($string) {
        $string = pathinfo($string, PATHINFO_FILENAME);
        $string = trim(preg_replace('/[^a-zA-Z0-9]/', ' ', $string));
        $string = ucwords($string);
        return $string;
    }
}
