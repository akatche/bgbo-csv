# Code Challenge for BGBO Co

## Architecture
I have chosen to use the following technologies:
- Laravel 10
- Vue 3
- Inertia.js
- Tailwind CSS
- MySQL
- Docker (Laravel Sail)
- Redis
- PHPUnit
- Soketi (for real-time updates)

## Architectural Decisions
I have chosen to use Vue.js + Inertia because I like how they interact together when using Laravel. On small-to-medium teams where all members are full-stack I consider Inertia a tool that is really worth it.

Mainly because it allows developers to go as fast as possible without having to deal with some issues that tend to happen when you have 2 separate projects (one for banckend and one for frontend).

Finally, the election of Soketi is because I wanted to try it out. I have used Laravel Echo with Pusher in the past and I wanted to see how Soketi compares to it, and it is perfect and also scales well.

## Design Decisions
I tried to replicate the color structure that you have on your main website. I also tried to make the UI as simple as possible.
And one addon that I did was to add the results logger visually and with real-time updates on its sending status.

## Testing
I have added tests that covers the main features of the app. They can be triggered by running the following command on the root of the project with Laravel Sail Installed.
```
./vendor/bin/sail artisan test
```
