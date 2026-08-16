{{--
    Theme bootstrap — now a single-theme guarantee.

    ─────────────────────────────────────────────────────────────────────────
    DARK MODE HAS BEEN RETIRED (supersedes ADR-15 / DESIGN_SYSTEM.md §2)

    The product previously shipped a user-toggled dark theme, defaulting to
    dark, persisted in localStorage. It has been withdrawn: the marketing
    surface, the auth screens and the app now share one light canvas, so the
    site no longer switches tone from section to section.

    This file is deliberately KEPT rather than deleted, and it deliberately
    still runs on every page, for two reasons:

      1. Returning visitors have `mada-theme: "dark"` sitting in their
         browser from before the change. Nothing reads that key any more, but
         leaving it there means a later feature that reintroduces a stored
         preference would silently inherit a choice made against a palette that
         no longer exists. It is cleared once, here.

      2. `wire:navigate` copies the incoming document's <html> attributes over
         the live element (livewire.js `swapCurrentPageWithNewHtml`). Any
         `dark` class left on <html> by a cached page, an extension, or a
         browser restoring a bfcache entry would survive that swap. Stripping
         it after every navigation is what makes "light only" actually true at
         runtime rather than only true in the templates.

    The `dark:` variants still present throughout the markup are now inert:
    nothing ever adds the class that activates them. They are left in place so
    the change is reversible in one file, and are removed opportunistically as
    each view is next touched.
    ─────────────────────────────────────────────────────────────────────────
--}}
<script>
    (function () {
        function forceLightTheme() {
            document.documentElement.classList.remove('dark');
        }

        forceLightTheme();

        // Private-mode and blocked-storage browsers throw on access rather than
        // returning null. Losing the cleanup is acceptable; taking the whole
        // page down before it paints is not.
        try {
            localStorage.removeItem('mada-theme');
        } catch (e) {}

        // Registered on `document`, which survives the body swap, so one
        // registration covers every subsequent Livewire navigation.
        document.addEventListener('livewire:navigated', forceLightTheme);
    })();
</script>
