<?php

namespace App\Domain\Messaging\Support;

/**
 * The composer's emoji palette, grouped into tabs.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * NOT THE SAME LIST AS ToggleReactionAction::ALLOWED
 *
 * Reactions are a six-emoji whitelist because a reaction is written into
 * everyone else's message and is rendered as a pill the recipient never
 * chose. This catalog goes into `body`, which is escaped on render like any
 * other text, so it does not need to be constrained for safety — only
 * curated so the picker stays navigable.
 *
 * Kept in PHP rather than inline in the Blade view because it is ~250
 * entries: in the view it would bury the markup, and here it can be reused
 * by any surface that needs a picker.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class EmojiCatalog
{
    /**
     * Tabs, in display order.
     *
     * `icon` is the tab's own glyph — an icon font would need a bespoke
     * pictogram per category, whereas one representative emoji says exactly
     * what the tab holds in any locale.
     *
     * @return list<array{key: string, label: string, icon: string, emoji: list<string>}>
     */
    public static function categories(): array
    {
        return [
            [
                'key' => 'smileys',
                'label' => 'الوجوه',
                'icon' => '😀',
                'emoji' => [
                    '😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂',
                    '🙂', '🙃', '😉', '😊', '😇', '🥰', '😍', '😘',
                    '😋', '😛', '😜', '🤪', '🤗', '🤭', '🤔', '🤐',
                    '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '😌',
                    '😔', '😪', '😴', '🥱', '😷', '🤒', '🤕', '🥵',
                    '🥶', '😵', '🤯', '🥳', '😎', '🤓', '🧐', '😕',
                    '😟', '🙁', '😯', '😲', '😳', '🥺', '😨', '😰',
                    '😢', '😭', '😱', '😖', '😞', '😓', '😩', '😤',
                ],
            ],
            [
                'key' => 'gestures',
                'label' => 'الإيماءات',
                'icon' => '👍',
                'emoji' => [
                    '👍', '👎', '👌', '🤌', '🤏', '✌️', '🤞', '🤟',
                    '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '✋',
                    '🤚', '🖐️', '🖖', '👋', '🤝', '🙏', '✍️', '💪',
                    '🦾', '👏', '🙌', '👐', '🤲', '🫶', '✊', '👊',
                    '🤛', '🤜', '🫡', '🤦', '🤷', '🙋', '🙆', '🙅',
                    '💁', '🧑‍💻', '👨‍💼', '👩‍💼', '🧑‍🏫', '👀', '🧠', '🫀',
                ],
            ],
            [
                'key' => 'hearts',
                'label' => 'المشاعر',
                'icon' => '❤️',
                'emoji' => [
                    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
                    '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
                    '💘', '💝', '💟', '✨', '🌟', '⭐', '💫', '🔥',
                    '💥', '💯', '⚡', '🎉', '🎊', '🎈', '🎁', '🏆',
                    '🥇', '🥈', '🥉', '🎖️', '👑', '💎', '🕊️', '🤩',
                ],
            ],
            [
                'key' => 'work',
                'label' => 'العمل',
                'icon' => '💼',
                'emoji' => [
                    '💼', '📁', '📂', '🗂️', '📅', '📆', '🗓️', '📇',
                    '📈', '📉', '📊', '📋', '📌', '📍', '📎', '🖇️',
                    '📏', '📐', '✂️', '🗃️', '🗄️', '🗑️', '🔒', '🔓',
                    '🔑', '🗝️', '🔨', '🛠️', '⚙️', '🧰', '🔗', '✉️',
                    '📧', '📨', '📩', '📤', '📥', '📦', '🏷️', '💰',
                    '💵', '💳', '🧾', '💻', '🖥️', '🖨️', '⌨️', '🖱️',
                    '📱', '☎️', '📞', '🕐', '⏰', '⏳', '🏢', '🏦',
                ],
            ],
            [
                'key' => 'symbols',
                'label' => 'رموز',
                'icon' => '✅',
                'emoji' => [
                    '✅', '☑️', '✔️', '❌', '❎', '⭕', '🚫', '⚠️',
                    '❗', '❓', '💡', '🔔', '🔕', '📢', '📣', '🔊',
                    '➕', '➖', '✖️', '➗', '♻️', '🔄', '🔃', '⬆️',
                    '⬇️', '⬅️', '➡️', '↗️', '↘️', '🔝', '🆕', '🆗',
                    '🆙', '⏸️', '▶️', '⏹️', '🎯', '🧭', '🔍', '🔎',
                    '⏱️', '🔴', '🟠', '🟡', '🟢', '🔵', '⚫', '⚪',
                ],
            ],
            [
                'key' => 'nature',
                'label' => 'متنوعة',
                'icon' => '🌿',
                'emoji' => [
                    '🌿', '🍀', '🌵', '🌴', '🌲', '🌳', '🌱', '🌾',
                    '🌸', '🌼', '🌻', '🌹', '🌺', '🌷', '💐', '🍁',
                    '🍂', '☀️', '🌤️', '⛅', '🌧️', '⛈️', '❄️', '🌈',
                    '🌙', '🌍', '☕', '🍵', '🥤', '🍽️', '🍕', '🍔',
                    '🥗', '🍞', '🧀', '🍎', '🍊', '🍇', '🍓', '🍉',
                    '🍰', '🎂', '🍫', '🍩', '✈️', '🚗', '🏠', '⚽',
                ],
            ],
        ];
    }
}
