<?php

function lead_validate(array $input, array $site): array
{
    throw new RuntimeException('não implementado');
}

function lead_insert(PDO $pdo, array $data, array $site, string $ip, string $userAgent): int
{
    throw new RuntimeException('não implementado');
}

function whatsapp_url(array $site, string $leadName = ''): string
{
    throw new RuntimeException('não implementado');
}
