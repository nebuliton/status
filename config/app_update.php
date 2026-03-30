<?php

return [
    'deploy_mode' => env('APP_UPDATE_DEPLOY_MODE', 'local'),
    'docker_service' => env('APP_UPDATE_DOCKER_SERVICE', 'app'),
];
