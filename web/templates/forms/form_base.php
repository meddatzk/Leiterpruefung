<?php
/**
 * Basis-Formular-Template
 * Stellt wiederverwendbare Formular-Komponenten zur Verfügung
 */

// Form Configuration
$form_config = array_merge([
    'method' => 'POST',
    'action' => '',
    'class' => 'form',
    'id' => '',
    'enctype' => '',
    'novalidate' => false,
    'autocomplete' => 'on'
], $form_config ?? []);

// Form Data
$form_data = $form_data ?? [];
$form_errors = $form_errors ?? [];
?>

<!-- Form Start -->
<form 
    method="<?= $this->e($form_config['method']) ?>"
    <?php if (!empty($form_config['action'])): ?>action="<?= $this->e($form_config['action']) ?>"<?php endif; ?>
    <?php if (!empty($form_config['class'])): ?>class="<?= $this->e($form_config['class']) ?>"<?php endif; ?>
    <?php if (!empty($form_config['id'])): ?>id="<?= $this->e($form_config['id']) ?>"<?php endif; ?>
    <?php if (!empty($form_config['enctype'])): ?>enctype="<?= $this->e($form_config['enctype']) ?>"<?php endif; ?>
    <?php if ($form_config['novalidate']): ?>novalidate<?php endif; ?>
    autocomplete="<?= $this->e($form_config['autocomplete']) ?>"
>
    <!-- CSRF Token -->
    <?= $this->csrfField() ?>
    
    <!-- Form Content -->
    <?= $this->section('form_content') ?>
    
</form>

<?php
/**
 * Formular-Helper-Funktionen
 */

// Text Input Field
function renderTextField($template, $name, $config = []) {
    $config = array_merge([
        'type' => 'text',
        'label' => '',
        'placeholder' => '',
        'required' => false,
        'readonly' => false,
        'disabled' => false,
        'class' => 'form-control',
        'help' => '',
        'value' => '',
        'attributes' => []
    ], $config);
    
    $value = $template->form_data[$name] ?? $config['value'];
    $error = $template->form_errors[$name] ?? '';
    $hasError = !empty($error);
    
    ob_start();
    ?>
    <div class="mb-3">
        <?php if (!empty($config['label'])): ?>
        <label for="<?= $template->e($name) ?>" class="form-label">
            <?= $template->e($config['label']) ?>
            <?php if ($config['required']): ?><span class="text-danger">*</span><?php endif; ?>
        </label>
        <?php endif; ?>
        
        <input 
            type="<?= $template->e($config['type']) ?>"
            id="<?= $template->e($name) ?>"
            name="<?= $template->e($name) ?>"
            class="<?= $template->e($config['class']) ?><?= $hasError ? ' is-invalid' : '' ?>"
            value="<?= $template->e($value) ?>"
            <?php if (!empty($config['placeholder'])): ?>placeholder="<?= $template->e($config['placeholder']) ?>"<?php endif; ?>
            <?php if ($config['required']): ?>required<?php endif; ?>
            <?php if ($config['readonly']): ?>readonly<?php endif; ?>
            <?php if ($config['disabled']): ?>disabled<?php endif; ?>
            <?php foreach ($config['attributes'] as $attr => $attrValue): ?>
                <?= $template->e($attr) ?>="<?= $template->e($attrValue) ?>"
            <?php endforeach; ?>
        >
        
        <?php if ($hasError): ?>
        <div class="invalid-feedback">
            <?= $template->e($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($config['help'])): ?>
        <div class="form-text">
            <?= $template->e($config['help']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Select Field
function renderSelectField($template, $name, $options, $config = []) {
    $config = array_merge([
        'label' => '',
        'required' => false,
        'disabled' => false,
        'class' => 'form-select',
        'help' => '',
        'value' => '',
        'multiple' => false,
        'attributes' => []
    ], $config);
    
    $value = $template->form_data[$name] ?? $config['value'];
    $error = $template->form_errors[$name] ?? '';
    $hasError = !empty($error);
    
    ob_start();
    ?>
    <div class="mb-3">
        <?php if (!empty($config['label'])): ?>
        <label for="<?= $template->e($name) ?>" class="form-label">
            <?= $template->e($config['label']) ?>
            <?php if ($config['required']): ?><span class="text-danger">*</span><?php endif; ?>
        </label>
        <?php endif; ?>
        
        <select 
            id="<?= $template->e($name) ?>"
            name="<?= $template->e($name) ?><?= $config['multiple'] ? '[]' : '' ?>"
            class="<?= $template->e($config['class']) ?><?= $hasError ? ' is-invalid' : '' ?>"
            <?php if ($config['required']): ?>required<?php endif; ?>
            <?php if ($config['disabled']): ?>disabled<?php endif; ?>
            <?php if ($config['multiple']): ?>multiple<?php endif; ?>
            <?php foreach ($config['attributes'] as $attr => $attrValue): ?>
                <?= $template->e($attr) ?>="<?= $template->e($attrValue) ?>"
            <?php endforeach; ?>
        >
            <?php foreach ($options as $optValue => $optLabel): ?>
            <option value="<?= $template->e($optValue) ?>" <?= ($value == $optValue) ? 'selected' : '' ?>>
                <?= $template->e($optLabel) ?>
            </option>
            <?php endforeach; ?>
        </select>
        
        <?php if ($hasError): ?>
        <div class="invalid-feedback">
            <?= $template->e($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($config['help'])): ?>
        <div class="form-text">
            <?= $template->e($config['help']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Textarea Field
function renderTextareaField($template, $name, $config = []) {
    $config = array_merge([
        'label' => '',
        'placeholder' => '',
        'required' => false,
        'readonly' => false,
        'disabled' => false,
        'class' => 'form-control',
        'help' => '',
        'value' => '',
        'rows' => 3,
        'attributes' => []
    ], $config);
    
    $value = $template->form_data[$name] ?? $config['value'];
    $error = $template->form_errors[$name] ?? '';
    $hasError = !empty($error);
    
    ob_start();
    ?>
    <div class="mb-3">
        <?php if (!empty($config['label'])): ?>
        <label for="<?= $template->e($name) ?>" class="form-label">
            <?= $template->e($config['label']) ?>
            <?php if ($config['required']): ?><span class="text-danger">*</span><?php endif; ?>
        </label>
        <?php endif; ?>
        
        <textarea 
            id="<?= $template->e($name) ?>"
            name="<?= $template->e($name) ?>"
            class="<?= $template->e($config['class']) ?><?= $hasError ? ' is-invalid' : '' ?>"
            rows="<?= $template->e($config['rows']) ?>"
            <?php if (!empty($config['placeholder'])): ?>placeholder="<?= $template->e($config['placeholder']) ?>"<?php endif; ?>
            <?php if ($config['required']): ?>required<?php endif; ?>
            <?php if ($config['readonly']): ?>readonly<?php endif; ?>
            <?php if ($config['disabled']): ?>disabled<?php endif; ?>
            <?php foreach ($config['attributes'] as $attr => $attrValue): ?>
                <?= $template->e($attr) ?>="<?= $template->e($attrValue) ?>"
            <?php endforeach; ?>
        ><?= $template->e($value) ?></textarea>
        
        <?php if ($hasError): ?>
        <div class="invalid-feedback">
            <?= $template->e($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($config['help'])): ?>
        <div class="form-text">
            <?= $template->e($config['help']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Checkbox Field
function renderCheckboxField($template, $name, $config = []) {
    $config = array_merge([
        'label' => '',
        'help' => '',
        'value' => '1',
        'checked' => false,
        'disabled' => false,
        'class' => 'form-check-input',
        'attributes' => []
    ], $config);
    
    $checked = $template->form_data[$name] ?? $config['checked'];
    $error = $template->form_errors[$name] ?? '';
    $hasError = !empty($error);
    
    ob_start();
    ?>
    <div class="mb-3">
        <div class="form-check">
            <input 
                type="checkbox"
                id="<?= $template->e($name) ?>"
                name="<?= $template->e($name) ?>"
                class="<?= $template->e($config['class']) ?><?= $hasError ? ' is-invalid' : '' ?>"
                value="<?= $template->e($config['value']) ?>"
                <?php if ($checked): ?>checked<?php endif; ?>
                <?php if ($config['disabled']): ?>disabled<?php endif; ?>
                <?php foreach ($config['attributes'] as $attr => $attrValue): ?>
                    <?= $template->e($attr) ?>="<?= $template->e($attrValue) ?>"
                <?php endforeach; ?>
            >
            
            <?php if (!empty($config['label'])): ?>
            <label for="<?= $template->e($name) ?>" class="form-check-label">
                <?= $template->e($config['label']) ?>
            </label>
            <?php endif; ?>
            
            <?php if ($hasError): ?>
            <div class="invalid-feedback">
                <?= $template->e($error) ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($config['help'])): ?>
        <div class="form-text">
            <?= $template->e($config['help']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// File Upload Field
function renderFileField($template, $name, $config = []) {
    $config = array_merge([
        'label' => '',
        'required' => false,
        'disabled' => false,
        'class' => 'form-control',
        'help' => '',
        'accept' => '',
        'multiple' => false,
        'attributes' => []
    ], $config);
    
    $error = $template->form_errors[$name] ?? '';
    $hasError = !empty($error);
    
    ob_start();
    ?>
    <div class="mb-3">
        <?php if (!empty($config['label'])): ?>
        <label for="<?= $template->e($name) ?>" class="form-label">
            <?= $template->e($config['label']) ?>
            <?php if ($config['required']): ?><span class="text-danger">*</span><?php endif; ?>
        </label>
        <?php endif; ?>
        
        <input 
            type="file"
            id="<?= $template->e($name) ?>"
            name="<?= $template->e($name) ?><?= $config['multiple'] ? '[]' : '' ?>"
            class="<?= $template->e($config['class']) ?><?= $hasError ? ' is-invalid' : '' ?>"
            <?php if (!empty($config['accept'])): ?>accept="<?= $template->e($config['accept']) ?>"<?php endif; ?>
            <?php if ($config['required']): ?>required<?php endif; ?>
            <?php if ($config['disabled']): ?>disabled<?php endif; ?>
            <?php if ($config['multiple']): ?>multiple<?php endif; ?>
            <?php foreach ($config['attributes'] as $attr => $attrValue): ?>
                <?= $template->e($attr) ?>="<?= $template->e($attrValue) ?>"
            <?php endforeach; ?>
        >
        
        <?php if ($hasError): ?>
        <div class="invalid-feedback">
            <?= $template->e($error) ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($config['help'])): ?>
        <div class="form-text">
            <?= $template->e($config['help']) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Form Actions (Submit/Cancel Buttons)
function renderFormActions($template, $config = []) {
    $config = array_merge([
        'submit_text' => 'Speichern',
        'submit_class' => 'btn btn-primary',
        'cancel_text' => 'Abbrechen',
        'cancel_class' => 'btn btn-secondary',
        'cancel_url' => '',
        'show_cancel' => true,
        'additional_buttons' => []
    ], $config);
    
    ob_start();
    ?>
    <div class="form-actions">
        <div class="d-flex gap-2">
            <button type="submit" class="<?= $template->e($config['submit_class']) ?>">
                <i class="bi bi-check-lg"></i> <?= $template->e($config['submit_text']) ?>
            </button>
            
            <?php if ($config['show_cancel']): ?>
            <?php if (!empty($config['cancel_url'])): ?>
            <a href="<?= $template->e($config['cancel_url']) ?>" class="<?= $template->e($config['cancel_class']) ?>">
                <i class="bi bi-x-lg"></i> <?= $template->e($config['cancel_text']) ?>
            </a>
            <?php else: ?>
            <button type="button" class="<?= $template->e($config['cancel_class']) ?>" onclick="history.back()">
                <i class="bi bi-x-lg"></i> <?= $template->e($config['cancel_text']) ?>
            </button>
            <?php endif; ?>
            <?php endif; ?>
            
            <?php foreach ($config['additional_buttons'] as $button): ?>
            <button type="<?= $template->e($button['type'] ?? 'button') ?>" class="<?= $template->e($button['class'] ?? 'btn btn-outline-secondary') ?>">
                <?php if (!empty($button['icon'])): ?><i class="bi <?= $template->e($button['icon']) ?>"></i><?php endif; ?>
                <?= $template->e($button['text']) ?>
            </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Mache Helper-Funktionen in Template verfügbar
$this->set('renderTextField', function($name, $config = []) use ($template) {
    return renderTextField($template, $name, $config);
});

$this->set('renderSelectField', function($name, $options, $config = []) use ($template) {
    return renderSelectField($template, $name, $options, $config);
});

$this->set('renderTextareaField', function($name, $config = []) use ($template) {
    return renderTextareaField($template, $name, $config);
});

$this->set('renderCheckboxField', function($name, $config = []) use ($template) {
    return renderCheckboxField($template, $name, $config);
});

$this->set('renderFileField', function($name, $config = []) use ($template) {
    return renderFileField($template, $name, $config);
});

$this->set('renderFormActions', function($config = []) use ($template) {
    return renderFormActions($template, $config);
});
?>
