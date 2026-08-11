{{--
    Theme bootstrap — the single definition of "dark is the default" (ADR-15,
    DESIGN_SYSTEM.md §2).

    MUST be rendered inside <head> BEFORE the stylesheet. It is deliberately a
    blocking inline script: the `dark` class has to be on <html> before first
    paint, or the page flashes light and then snaps dark.

    ─────────────────────────────────────────────────────────────────────────
    WHY THIS IS A COMPONENT AND NOT SEVEN COPIES

    This logic previously lived inline in six separate layouts. The seventh
    surface that needed it — the disabled company-portal page — was written
    without it, so it rendered light for everyone regardless of their stored
    preference. That is the failure mode duplication guarantees eventually:
    every new page that owns its own <html> is one oversight away from the same
    bug. One component means a page either has the behaviour or visibly does
    not include it.

    Exempt by design: reports/print/* and finance/*/print.blade.php render in a
    fixed light theme because printed output must not follow screen settings
    (DESIGN_SYSTEM.md §2.2), and errors/403 + errors/404 are always-dark brand
    surfaces with no `dark:` variants to switch.
    ─────────────────────────────────────────────────────────────────────────

    Storage holds ONLY an explicit user choice. A visitor who has never touched
    the toggle has no key at all, which is what keeps the default a property of
    this file rather than something already burned into every existing
    visitor's browser — change the fallback here and it takes effect for
    everyone who never expressed a preference.
--}}
<script>
    (function () {
        function applyTheme() {
            var stored = null;

            // Private-mode and blocked-storage browsers throw on access rather
            // than returning null. Losing the preference is acceptable; taking
            // the whole page down before it paints is not.
            try {
                stored = localStorage.getItem('veyra-theme');
            } catch (e) {}

            document.documentElement.classList.toggle('dark', stored !== 'light');
        }

        applyTheme();

        /*
         * ─────────────────────────────────────────────────────────────────
         * RE-APPLY AFTER EVERY wire:navigate
         *
         * The tenant sidebar and top bar navigate with `wire:navigate`, and
         * Livewire's page swap calls `replaceHtmlAttributes()` — it copies the
         * incoming document's <html> attributes onto the live element and
         * REMOVES any the new document does not have (livewire.js v3.8.2,
         * `swapCurrentPageWithNewHtml`).
         *
         * The server renders `class="h-full scroll-smooth"` with no `dark`,
         * because `dark` is added here, client-side, from localStorage. So
         * every sidebar click stripped it and the dashboard fell back to
         * light — permanently, until a full reload, since Livewire also does
         * not re-run an unchanged inline <head> script.
         *
         * The listener is registered on `document`, which survives the body
         * swap, so one registration covers every subsequent navigation.
         * ─────────────────────────────────────────────────────────────────
         */
        document.addEventListener('livewire:navigated', applyTheme);
    })();
</script>
