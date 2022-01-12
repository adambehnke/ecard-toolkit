<?php
namespace eCard;

class Modal {
    private $template;
    private $title;
    private $content;
    private $content_template;
    private $status_animation;
    private $hidden_field;
    private $show_buttons;
    private $show_close;

    public function __construct() {
        $this->class = 'modal-ecard';
        $this->template = MODAL_PATH . 'templates/modal.php';
        $this->title = '';
        $this->show_buttons = true;
        $this->show_close = true;
        $this->status_animation = false;
        $this->hidden_fields = array();
        $this->buttons = array(
            'cancel' => 'Cancel',
            'confirm' => 'Confirm'
        );
    }

    /**
     * Sets the modal title.
     *
     * @param string $title Modal title.
     */
    public function set_title($title) {
        $this->title = $title;
    }

    /**
     * Sets the modal content.
     * 
     * @param string $content Modal content.
     */
    public function set_content($content) {
        $this->content = $content;
    }

    /**
     * Sets the content template and adds it to the content.
     * 
     * @param string $template The path to the template
     */
    public function set_content_template($content_template) {
        $this->content_template = $content_template;

        ob_start();
		include $this->content_template;
        $content = ob_get_clean();

        $this->content = $content;
    }

    /**
     * Sets an empty div as the modal content.
     * 
     * @param string $class Optional class for the empty div.
     * @param boolean $empty_on_close If true, the div will be emptied out when the modal is closed.
     */
    public function set_empty_content($class = '', $empty_on_close = false) {
        $attr = '';

        if ($empty_on_close) {
            $class .= ' empty-on-close';
        }

        if ($class !== '') {
            $class = ltrim($class);
            $attr .= ' class="' . $class . '"';
        }
        
        $this->set_content('<div' . $attr . '></div>');
    }

    /**
     * Sets the button text.
     * 
     * @param string $button Button key.
     * @param string $button Button text.
     */
    public function set_button_text($button, $text) {
        $this->buttons[$button] = $text;
    }

    /**
     * Sets the modal class.
     * 
     * @param string $class Modal class.
     */
    public function set_class($class) {
        $this->class .= ' ' . $class;
    } 

    /**
     * Sets the modal nonce.
     * 
     * @param string $class Modal nonce.
     */
    public function set_nonce($nonce) {
        $this->nonce = $nonce;
    }

    /**
     * Toggles the status animation. Sets the accompanying class.
     * 
     * @param boolean $boolean If true, the animation is turned on.
     */
    public function set_status_animation($boolean) {
        $this->status_animation = $boolean;

        if ($this->status_animation) {
            $this->set_class('has-status-animation');
        }
    }

    /**
     * Sets the status animation text.
     * 
     * @param boolean $text The text to be shown beneath the animation.
     */
    public function set_status_animation_text($text) {
        $this->status_animation_text = $text;
    }

    /**
     * Sets the hidden field.
     * 
     * @param string $name The name of the hidden field.
     * @param string $value The value of the hidden field.
     */
    public function set_hidden_field($name, $value) {
        $this->hidden_fields[$name] = $value;
    }

    /**
     * Sets the show buttons variable.
     * 
     * @param boolean $boolean When set to true, the buttons will be shown, when false, the buttons will be hidden.
     */
    public function set_show_buttons($boolean) {
        $this->show_buttons = $boolean;
    }

    /**
     * Sets the $show_close variable.
     * 
     * @param boolean $boolean When set to true, the close icon will be shown, when false, the icon will be hidden.
     */
    public function set_show_close($boolean) {
        $this->show_close = $boolean;
    }

    /**
     * Generates the modal.
     */
    public function generate() {
        $html = '';

        ob_start();
	include $this->template;
        $html = ob_get_clean();

        return $html;
    }
}
