<?php

return [
    'jira-url' => 'https://your-org.atlassian.net',
    'jira-user' => 'user@example.com',
    'jira-token' => '...',
    'project-regex' => '/^([A-Z]+)-\d+/',
    'protected-branches' => ['main', 'dev', 'stage', 'beta', 'master'],
    'branch-rules' => [
        'beta'  => ['match' => '/(?P<major>\d+)\.(?P<minor>\d+)\.1$/'],
        'stage' => ['match' => '/{major}\.{minor+1}\.0$/'],
        'default' => 'dev',
    ],
    'gitlab-url' => 'https://gitlab.example.com',
    'gitlab-token' => '...',
    'container' => 'your-app',
    'compose-service' => 'app',
];
