<div class="<?php echo $this->class; ?>">
    <div class="modal-inner">
        <div class="modal-box-container">
            <?php if ($this->title !== ''): ?>
            <div class="modal-header">
                <h2><?php echo $this->title; ?></h2>
            </div>
            <?php endif; ?>
            <?php if (isset($this->content)): ?>
            <div class="modal-content">
                <?php echo $this->content; ?>
            </div>
            <?php endif; ?>
            <?php if ($this->show_buttons): ?>
            <div class="modal-buttons">
                <span class="cancel close-modal"><?php echo $this->buttons['cancel']; ?></span>
                <span class="confirm"><?php echo $this->buttons['confirm']; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($this->status_animation): ?>
                <div class="status-animation">
                    <i class="fa fa-asterisk icon-spinner"></i>

                    <?php if (isset($this->status_animation_text)): ?>
                        <p class="status-animation-text"><?php echo esc_html($this->status_animation_text); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>    
    <?php if ($this->show_close): ?>
    <span class="close-modal">&#xd7;</span>
    <?php endif; ?>
    <?php if (isset($this->nonce)): ?>
        <input type="hidden" name="modal-nonce" value="<?php echo $this->nonce; ?>" />
    <?php endif; ?>

    <?php if (!empty($this->hidden_fields)): ?>
        <?php foreach ($this->hidden_fields as $name => $value): ?>
        <input type="hidden" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" />
        <?php endforeach; ?>
    <?php endif; ?>
</div>
