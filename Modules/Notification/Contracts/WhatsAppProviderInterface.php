<?php

namespace Modules\Notification\Contracts;

interface WhatsAppProviderInterface
{
    public function sendMessage(string $target, string $message): bool;

    public function getLastError(): ?string;
}
