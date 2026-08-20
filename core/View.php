<?php
declare(strict_types=1);

namespace Core;

final class View
{
    /** Render a template from /views and return the markup. */
    public static function render(string $template, array $data = []): string
    {
        $file = APP_ROOT . '/views/' . ltrim($template, '/') . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('View not found: ' . $template);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }

    /**
     * Render a template inside a layout, which receives $content.
     * A bare name resolves under views/layouts; a path is used as given.
     */
    public static function page(string $layout, string $template, array $data = []): string
    {
        $content = self::render($template, $data);
        $layoutPath = str_contains($layout, '/') ? $layout : 'layouts/' . $layout;
        // The rendered markup always wins, even when the template's own data
        // happens to use a "content" key.
        return self::render($layoutPath, array_merge($data, ['content' => $content]));
    }

    public static function exists(string $template): bool
    {
        return is_file(APP_ROOT . '/views/' . ltrim($template, '/') . '.php');
    }
}
