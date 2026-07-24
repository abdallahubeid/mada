<?php

namespace App\Support;

/**
 * Lightweight Flasher-compatible notifier that surfaces messages via SweetAlert.
 */
class FlashNotifier
{
    public function success(string $message): void
    {
        $this->flash('success', $message);
    }

    public function info(string $message): void
    {
        $this->flash('info', $message);
    }

    public function warning(string $message): void
    {
        $this->flash('warning', $message);
    }

    public function error(string $message): void
    {
        $this->flash('error', $message);
    }

    public function danger(string $message): void
    {
        $this->flash('error', $message);
    }

    private function flash(string $type, string $message): void
    {
        session()->flash('flasher', [
            'type' => $type,
            'message' => $message,
        ]);
    }
}
