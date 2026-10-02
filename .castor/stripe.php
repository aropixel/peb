<?php

namespace stripe;

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\io;
use function Castor\load_dot_env;
use function Castor\variable;
use function docker\docker_compose;

#[AsTask(description: 'Relays Stripe test webhooks to the local application (stripe listen)')]
function listen(): void
{
    $env = load_dot_env(variable('root_dir') . '/application/.env');
    $apiKey = $env['STRIPE_SECRET_KEY'] ?? '';

    if (!\is_string($apiKey) || !str_starts_with($apiKey, 'sk_test_')) {
        io()->error('Set a Stripe test key (sk_test_...) as STRIPE_SECRET_KEY in application/.env.local first.');

        return;
    }

    io()->title('Relaying Stripe webhooks to /stripe/webhook');
    io()->note('Copy the "whsec_..." secret printed below into STRIPE_WEBHOOK_SECRET in application/.env.local.');

    docker_compose(
        ['run', '--rm', 'stripe', 'listen', '--forward-to', 'http://frontend/stripe/webhook'],
        c: context()->withEnvironment(['STRIPE_API_KEY' => $apiKey])->withTimeout(null)->toInteractive(),
        profiles: ['default', 'stripe'],
    );
}
