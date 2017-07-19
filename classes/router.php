<?php

namespace Logistica\Classes;

require_once join(DIRECTORY_SEPARATOR, [__DIR__, '..', 'tools', 'ex-string.php']);

use Exception;
use Logistica\Tools\ExString;

// error for class Router
class RouterError extends Exception {}

class Router {

  private $path;
  private $prefix;
  private $routes = array();

  private function utrim (string $before) {
    return trim($before, '/');
  }

  public function __construct (string $prefix = NULL) {
    $this->prefix = utrim($prefix);
    $this->path = utrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
  }

  public function add_route (string $origin_name, callable $callback) {
    $name = utrim($origin_name);
    if (isset($this->routes[$name])) throw new RouterError("You can't override exstent route.");
    $this->routes[$name] = $callback;
  }

  public function remove_route (string $origin_name) {
    $name = utrim($origin_name);
    if (!isset($this->routes[$name])) throw new RouterError("You can't remove non-exstent route.");
    unset($this->routes[$name]);
  }

  public function exec (): bool {
    // OK with no prefix, kick!
    if (!isset($this->prefix)) return $this->search_and_exec($this->path);

    list($prefix, $path) = explode('/', $this->path, 2);
    // prefix exists, but prefix not match
    if ($prefix !== $this->prefix) return false;
    // OK with prefix, kick!
    return $this->search_and_exec($path);
  }

  private function search_and_exec (string $path) {
    foreach ($this->routes as $route => $callback) {
      $route_factors = trim($route, '/');
      $path_match_regexp_pattern = '/^';
      $path_match_dynamic_element_names = array();

      foreach ($route_factors as $route_factor) {
        if (!ExString::startsWith($route_factor, ':')) {
          $path_match_regexp_pattern .= $route_factor + '\/';
          continue;
        }
        array_push($path_match_dynamic_element_names, mb_strcut($route_factor, 1));
        $path_match_regexp_pattern .= '([^\/]*?)\/';
      }

      $path_match_dynamic_element_values = array();
      $path_match_result = mb_ereg($path_match_regexp_pattern, $path, $path_match_dynamic_element_values);

      if ($path_match_result === false) return false;
      array_shift($path_match_dynamic_element_values);

      return call_user_func($callback, $path_match_dynamic_element_values);
    }
  }

}
