<?php

namespace App\Support;

/**
 * Lightweight Flasher-compatible notifier that surfaces messages via SweetAlert.
 */
class FlashNotifier
{
    public function success(string $message, array $options = []): void
    {
        $this->flash('success', $message, $options);
    }

    public function info(string $message, array $options = []): void
    {
        $this->flash('info', $message, $options);
    }

    public function warning(string $message, array $options = []): void
    {
        $this->flash('warning', $message, $options);
    }

    public function error(string $message, array $options = []): void
    {
        $this->flash('error', $message, $options);
    }

    public function danger(string $message, array $options = []): void
    {
        $this->flash('error', $message, $options);
    }

    /**
     * @param  array{undo_url?: string, undo_label?: string, undo_method?: string}  $options
     */
    private function flash(string $type, string $message, array $options = []): void
    {
        $payload = [
            'type' => $type,
            'message' => $message,
        ];

        if (! empty($options['undo_url'])) {
            $payload['undo_url'] = $options['undo_url'];
            $payload['undo_label'] = $options['undo_label'] ?? 'تراجع';
            $payload['undo_method'] = strtoupper($options['undo_method'] ?? 'POST');
        }

        session()->flash('flasher', $payload);
    }
}
