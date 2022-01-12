<?php
class Order_Dropdown {
    private $label;
    private $data;
    private $current;
    private $default;
    private $attributes;
    private $attributesOption;
    private $defaultOption;

    public function __construct($args) {
        $this->label = isset($args['label']) ? $args['label']: '';
        $this->data = isset($args['data']) ? $args['data'] : array();
        $this->default = isset($args['default']) ? $args['default'] : '';
        $this->attributes = isset($args['attributes']) ? $args['attributes'] : array();
        $this->attributesOption = isset($args['attributes_option']) ? $args['attributes_option'] : array();
        $this->current = isset($args['current']) ? $args['current'] : '';
    }

    public function generate($textAsKey = false) {
        $html  = '';

        $html .= $this->generateLabel();
        $html .= $this->generateOpeningTag();
        $html .= $this->generateDefaultOption();
        $html .= $this->generateOptions($textAsKey);
        $html .= $this->generateClosingTag();

        return $html;
    }

    private function generateLabel() {
        $html = '';

        if ($this->label !== '') {
            $html .= '<label>' . $this->label . '</label>';
        }

        return $html;
    }

    private function generateOpeningTag() {
        $html = '';
        $attributesString = ' ';
        
        foreach ($this->attributes as $key => $value) {
            if ($key === 'class') {
                $value = 'form-control ' . $value;
            }

            if (!in_array($key, array('class', 'name', 'id', 'disabled')) && strpos($key, 'data-') === false) {
                $key = 'data-' . $key;
            }

            if ($key === 'disabled' && $value === true) {
                $attributesString .= " $key";
            } elseif ($key !== 'disabled' || $key === 'disabled' && $value !== false) {
                $attributesString .= " $key='$value'";
            }
        }

        $html .= "<select$attributesString>";

        return $html;
    }

    private function generateDefaultOption() {
        $html = '';

        if ($this->default !== '') {
            $html .= '<option value="">' . $this->default .'</option>';
        }

        return $html;
    }

    private function generateOptions($textAsKey) {
        $html = '';

        foreach ($this->data as $key => $value) {
            if ($textAsKey) {
                if (is_array($value) && isset($value['text'])) {
                    $optionValue = $value['text'];
                } else {
                    $optionValue = $value;
                }
            } else {
                $optionValue = $key;
            }

            $text = is_array($value) && isset($value['text']) ? $value['text'] : $value;

            $attributesOptionString = ($this->current == $optionValue) ? ' selected' : '';

            if (is_array($value) && isset($value['attributes'])) {
                foreach ($value['attributes'] as $attribute_key => $attribute_value) {
                    $prefix = !in_array($attribute_key, array('id', 'class')) ? 'data-' : '';
                    $attributesOptionString .= ' ' . $prefix . $attribute_key;
                    $attributesOptionString .= '="' . $attribute_value . '"';
                }
            }

            $html .= "<option value='$optionValue'$attributesOptionString>$text</option>";
        }

        return $html;
    }

    private function generateClosingTag() {
        return "</select>";
    }
}