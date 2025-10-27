<?php
/**
 * ButtonHelper - Reusable Button Component Helper
 * Generates consistent button HTML across the application
 */

class ButtonHelper
{
    /**
     * Render a standardized button
     *
     * @param array $config Button configuration
     * @return string HTML for the button
     *
     * Usage:
     * echo ButtonHelper::render([
     *     'text' => 'Save',
     *     'type' => 'submit',
     *     'style' => 'primary',
     *     'icon' => 'check-circle',
     *     'size' => 'sm',
     *     'attributes' => ['id' => 'saveBtn']
     * ]);
     */
    public static function render(array $config): string
    {
        $text = $config['text'] ?? 'Button';
        $type = $config['type'] ?? 'button';
        $style = $config['style'] ?? 'primary';
        $icon = $config['icon'] ?? null;
        $iconPosition = $config['iconPosition'] ?? 'left';
        $size = $config['size'] ?? '';
        $outline = $config['outline'] ?? false;
        $attributes = $config['attributes'] ?? [];
        $classes = $config['classes'] ?? [];

        // Build button classes
        $btnClasses = ['btn'];
        $btnClasses[] = $outline ? "btn-outline-{$style}" : "btn-{$style}";
        if ($size) {
            $btnClasses[] = "btn-{$size}";
        }
        $btnClasses = array_merge($btnClasses, $classes);

        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        // Build icon HTML
        $iconHtml = '';
        if ($icon) {
            $iconSpacing = $iconPosition === 'left' ? 'me-1' : 'ms-1';
            $iconHtml = sprintf('<i class="bi bi-%s %s" aria-hidden="true"></i>', htmlspecialchars($icon), $iconSpacing);
        }

        // Build button HTML
        $html = sprintf(
            '<button type="%s" class="%s"%s>',
            htmlspecialchars($type),
            htmlspecialchars(implode(' ', $btnClasses)),
            $attrString
        );

        if ($icon && $iconPosition === 'left') {
            $html .= $iconHtml;
        }

        $html .= htmlspecialchars($text);

        if ($icon && $iconPosition === 'right') {
            $html .= $iconHtml;
        }

        $html .= '</button>';

        return $html;
    }

    /**
     * Render a link styled as a button
     *
     * @param array $config Link configuration
     * @return string HTML for the link button
     */
    public static function renderLink(array $config): string
    {
        $text = $config['text'] ?? 'Link';
        $url = $config['url'] ?? '#';
        $style = $config['style'] ?? 'primary';
        $icon = $config['icon'] ?? null;
        $iconPosition = $config['iconPosition'] ?? 'left';
        $size = $config['size'] ?? '';
        $outline = $config['outline'] ?? false;
        $attributes = $config['attributes'] ?? [];
        $classes = $config['classes'] ?? [];

        // Build button classes
        $btnClasses = ['btn'];
        $btnClasses[] = $outline ? "btn-outline-{$style}" : "btn-{$style}";
        if ($size) {
            $btnClasses[] = "btn-{$size}";
        }
        $btnClasses = array_merge($btnClasses, $classes);

        // Build attributes string
        $attrString = '';
        foreach ($attributes as $key => $value) {
            $attrString .= sprintf(' %s="%s"', htmlspecialchars($key), htmlspecialchars($value));
        }

        // Build icon HTML
        $iconHtml = '';
        if ($icon) {
            $iconSpacing = $iconPosition === 'left' ? 'me-1' : 'ms-1';
            $iconHtml = sprintf('<i class="bi bi-%s %s" aria-hidden="true"></i>', htmlspecialchars($icon), $iconSpacing);
        }

        // Build link HTML
        $html = sprintf(
            '<a href="%s" class="%s"%s>',
            htmlspecialchars($url),
            htmlspecialchars(implode(' ', $btnClasses)),
            $attrString
        );

        if ($icon && $iconPosition === 'left') {
            $html .= $iconHtml;
        }

        $html .= htmlspecialchars($text);

        if ($icon && $iconPosition === 'right') {
            $html .= $iconHtml;
        }

        $html .= '</a>';

        return $html;
    }

    /**
     * Render a button group
     *
     * @param array $buttons Array of button configurations
     * @param array $groupConfig Group configuration
     * @return string HTML for the button group
     */
    public static function renderGroup(array $buttons, array $groupConfig = []): string
    {
        $classes = $groupConfig['classes'] ?? [];
        $vertical = $groupConfig['vertical'] ?? false;
        $role = $groupConfig['role'] ?? 'group';
        $ariaLabel = $groupConfig['ariaLabel'] ?? 'Button group';

        $groupClasses = $vertical ? ['btn-group-vertical'] : ['btn-group'];
        $groupClasses = array_merge($groupClasses, $classes);

        $html = sprintf(
            '<div class="%s" role="%s" aria-label="%s">',
            htmlspecialchars(implode(' ', $groupClasses)),
            htmlspecialchars($role),
            htmlspecialchars($ariaLabel)
        );

        foreach ($buttons as $buttonConfig) {
            if (isset($buttonConfig['url'])) {
                $html .= self::renderLink($buttonConfig);
            } else {
                $html .= self::render($buttonConfig);
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Render workflow action buttons (common pattern: Back + Action)
     *
     * @param array $backConfig Back button configuration
     * @param array $actionConfig Action button configuration
     * @return string HTML for workflow action buttons
     */
    public static function renderWorkflowActions(array $backConfig, array $actionConfig): string
    {
        $backConfig = array_merge([
            'text' => 'Back to Details',
            'icon' => 'arrow-left',
            'style' => 'secondary',
            'outline' => false
        ], $backConfig);

        $html = '<div class="workflow-actions">';
        $html .= self::renderLink($backConfig);
        $html .= self::render($actionConfig);
        $html .= '</div>';

        return $html;
    }
}
