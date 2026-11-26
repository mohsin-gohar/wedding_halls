<?php

$lock = json_decode(file_get_contents(dirname(__DIR__).'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
$packages = collect($lock['packages'] ?? []);

$minimums = [
    'symfony/http-foundation' => '7.4.24',
    'symfony/http-kernel' => '7.4.24',
    'symfony/mailer' => '7.4.24',
    'symfony/routing' => '7.4.24',
];

$failures = [];
foreach ($minimums as $name => $minimum) {
    $package = $packages->firstWhere('name', $name);
    if (! $package) {
        $failures[] = "{$name} is missing from composer.lock";
        continue;
    }

    $version = ltrim((string) ($package['version'] ?? ''), 'v');
    if ($version === '' || version_compare($version, $minimum, '<')) {
        $failures[] = "{$name} {$version} is below required {$minimum}";
    } else {
        fwrite(STDOUT, "{$name}: {$version} (OK)".PHP_EOL);
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode(PHP_EOL, $failures).PHP_EOL);
    exit(1);
}
