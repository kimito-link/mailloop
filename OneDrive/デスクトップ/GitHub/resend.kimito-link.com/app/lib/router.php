<?php
declare(strict_types=1);

function route(string $method, string $path, callable $handler) {
  static $routes = [];
  $routes[] = [$method, $path, $handler];
  return $routes;
}
function dispatch(string $method, string $uri, array $ctx) {
  $path = parse_url($uri, PHP_URL_PATH) ?: '/';
  $routes = route('', '', fn()=>null);
  foreach ($routes as [$m, $p, $h]) {
    if ($m !== $method) continue;
    if ($p === $path) return $h($ctx);
  }
  http_response_code(404);
  echo "404 Not Found";
}
