<?php

declare(strict_types=1);

namespace App\Ports\Out;

use App\Application\Audit\DTO\AuditEventWrite;

interface AuditEventWriterPort
{
    public function write(AuditEventWrite $event): void;
}
