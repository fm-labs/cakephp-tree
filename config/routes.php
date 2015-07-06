<?php
use Cake\Routing\Router;

Router::plugin('Tree', function ($routes) {
    $routes->fallbacks('InflectedRoute');
});
