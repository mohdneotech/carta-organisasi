# Changelog

## 1.2.0 — 2026-11-09
- **Sebaris** (cards per row) setting on each tier: Auto or 1–6
- Auto keeps up to 4 cards on one row (Imam 1–4, Bilal 1–4); bigger groups split into rows of 3 or 4, whichever leaves fewer gaps (9 biro → 3×3)
- Cards shrink to fit narrow content columns instead of wrapping; photos scale with the card
- Live preview in wp-admin follows the same rule

## 1.1.0 — 2026-09-27
First public release (generalised from the Masjid Kampung Perepat build).
- Masjid-neutral defaults; starter template seeded on activation; "Muatkan templat asas" button
- Colour pickers (primary / accent) — no longer depends on theme CSS variables
- "Cipta halaman" button when no page uses `[carta_organisasi]`
- Works with block themes (styles registered on demand)
- `pc_capability` filter; uninstall cleanup; built-in GitHub Releases updater
- Refuses to activate alongside the old site-specific build (data is shared)
