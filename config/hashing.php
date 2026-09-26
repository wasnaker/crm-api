<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | argon2id, not bcrypt. Both embed a random per-hash salt, so neither needs
    | a separately configured SALT -- and adding one shared SALT to .env would
    | actively weaken things, because every user would then share a salt and
    | identical passwords would produce identical hashes, which is precisely
    | what salting exists to prevent.
    |
    | Argon2id is memory-hard. Bcrypt is CPU-bound and embarrassingly
    | parallel: a GPU can attempt billions of bcrypt hashes per second, while
    | argon2id forces 64 MiB per attempt, so the same GPU can only run a few
    | hundred. That memory requirement, not single-request latency, is the
    | reason to prefer it.
    |
    | Existing hashes keep verifying, because Hash::check() reads the algorithm
    | from the stored string. AuthController::login() calls Hash::needsRehash()
    | after a successful check, so accounts are transparently upgraded to
    | argon2id on their next login -- no forced re-login, no bulk UPDATE, and no
    | window where a new algorithm breaks existing credentials.
    |
    | Supported: "bcrypt", "argon", "argon2id"
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Retained so Hash::needsRehash() can still verify the *strength* of a
    | stored bcrypt hash. Raising this later will quietly upgrade bcrypt
    | accounts on their next login, the same way the driver switch does.
    |
    */

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),

        // Must stay false. Argon2IdHasher::check() throws a RuntimeException
        // when verifyAlgorithm is on and the stored hash is not argon2id, so
        // leaving this true would turn every pre-migration bcrypt login into a
        // 500 -- and Hash::needsRehash() would never get the chance to run,
        // because the exception fires inside Hash::check() first.
        //
        // With it off, check() falls through to password_verify(), which reads
        // the algorithm out of the stored hash string, so any algorithm still
        // verifies correctly. needsRehash() then upgrades accounts on login.
        // This is the only way to migrate the driver without locking anyone
        // out. Re-enable it only once every stored hash is argon2id.
        'verify' => env('BCRYPT_VERIFY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Defaults follow the OWASP Password Storage Cheat Sheet for argon2id
    | (m=64 MiB, t=4, p=1). They are env-overridable because the right values
    | depend on the deployment: each concurrent hash holds 64 MiB for its
    | duration, so a host that authenticates many simultaneous logins may need
    | to trade memory_cost down against time_cost, or raise PHP's
    | memory_limit to match.
    |
    */

    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536),
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),

        // See the bcrypt note above: this must stay false while any legacy
        // hash is still in the users table, or login throws instead of
        // upgrading.
        'verify' => env('ARGON_VERIFY', false),
    ],

];
