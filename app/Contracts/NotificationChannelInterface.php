<?php
namespace App\Contracts;

interface NotificationChannelInterface {
    public function send(string $to, string $message): bool;
}
