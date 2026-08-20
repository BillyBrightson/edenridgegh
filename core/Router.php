<?php
declare(strict_types=1);

namespace Core;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = ['GET' => [], 'POST' => []];
    /** @var array<int, array{0:string,1:string,2:callable}> */
    private array $patterns = [];
    /** @var null|callable */
    private $fallback = null;

    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** Register for both verbs. */
    public function any(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, callable $handler): void
    {
        if (str_contains($path, '{')) {
            $this->patterns[] = [$method, $path, $handler];
            return;
        }
        $this->routes[$method][rtrim($path, '/') ?: '/'] = $handler;
    }

    public function fallback(callable $handler): void
    {
        $this->fallback = $handler;
    }

    /** The path portion of the current request, normalised without a trailing slash. */
    public static function currentPath(): string
    {
        $uri  = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function dispatch(?string $path = null, ?string $method = null): void
    {
        $path   = $path   ?? self::currentPath();
        $method = $method ?? strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        foreach ($this->patterns as [$m, $pattern, $handler]) {
            if ($m !== $method) {
                continue;
            }
            $regex = '#^' . preg_replace('#\{([a-z_]+)\}#i', '(?P<$1>[^/]+)', $pattern) . '$#';
            if (preg_match($regex, $path, $matches)) {
                $args = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $handler($args);
                return;
            }
        }

        if ($this->fallback !== null) {
            ($this->fallback)();
            return;
        }
        http_response_code(404);
        echo 'Not found';
    }
}
