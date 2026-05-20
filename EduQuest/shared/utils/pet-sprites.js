/**
 * pet-sprites.js — Team-specific SVG visuals for eggs & pets
 *
 * Usage:
 *   PetSprites.get(team, stage)          → HTML string (SVG)
 *   PetSprites.getEvo(team)              → { 1: svg, 2: svg, … 5: svg }
 *   PetSprites.teamColor(team)           → primary hex colour
 *   PetSprites.stageName(team, stage)    → display name
 *
 * Teams: 'fire' | 'water' | 'grass'
 * Stages: 1 (Egg) → 2 (Cracking) → 3 (Hatchling) → 4 (Young) → 5 (Guardian)
 */
(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════
       COLOUR PALETTES
       ═══════════════════════════════════════════════════════ */
    const PALETTES = {
        fire:  { pri: '#ef4444', sec: '#f97316', acc: '#fbbf24', bg: '#fef2f2', glow: 'rgba(239,68,68,.35)' },
        water: { pri: '#3b82f6', sec: '#06b6d4', acc: '#67e8f9', bg: '#eff6ff', glow: 'rgba(59,130,246,.35)' },
        grass: { pri: '#22c55e', sec: '#16a34a', acc: '#a3e635', bg: '#f0fdf4', glow: 'rgba(34,197,94,.35)' },
    };

    /* ═══════════════════════════════════════════════════════
       STAGE NAMES (team-specific)
       ═══════════════════════════════════════════════════════ */
    const STAGE_NAMES = {
        fire:  { 1: 'Fire Egg',    2: 'Ember Egg',      3: 'Flame Pup',     4: 'Blaze Beast',    5: 'Inferno Guardian' },
        water: { 1: 'Water Egg',   2: 'Tide Egg',       3: 'Splash Pup',    4: 'Wave Rider',     5: 'Ocean Guardian' },
        grass: { 1: 'Leaf Egg',    2: 'Sprout Egg',     3: 'Seedling Pup',  4: 'Vine Beast',     5: 'Forest Guardian' },
    };

    const STAGE_DESCS = {
        fire: {
            1: 'A fiery egg radiating warmth. Earn XP to help it hatch!',
            2: 'Embers glow through the cracks — something fierce is coming!',
            3: 'A small flame pup has emerged! It sparks with energy!',
            4: 'Your companion blazes with growing power!',
            5: 'The Inferno Guardian has awakened — unstoppable!'
        },
        water: {
            1: 'A cool egg shimmering with water energy. Earn XP to hatch it!',
            2: 'Bubbles seep through the cracks — a splash is imminent!',
            3: 'A tiny splash pup paddles around curiously!',
            4: 'Your companion rides the waves with confidence!',
            5: 'The Ocean Guardian commands the tides — legendary!'
        },
        grass: {
            1: 'A verdant egg wrapped in tiny leaves. Earn XP to hatch it!',
            2: 'Sprouts push through the shell — life finds a way!',
            3: 'A playful seedling pup bounces among the vines!',
            4: 'Your companion grows tall and strong like an ancient tree!',
            5: 'The Forest Guardian protects the realm with nature\'s might!'
        },
    };

    /* ═══════════════════════════════════════════════════════
       SVG BUILDERS  (each returns an inline SVG string)
       Sizes target ~80×80 for the main display, but are
       viewBox-based so they scale with CSS.
       ═══════════════════════════════════════════════════════ */

    // ────────── FIRE ──────────
    const fire = {
        1: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="fEgg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fbbf24"/><stop offset="100%" stop-color="#ef4444"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#fEgg)"/><ellipse cx="40" cy="55" rx="28" ry="35" fill="none" stroke="#dc2626" stroke-width="1.5" opacity=".3"/><path d="M30 42 Q33 36 36 42" stroke="#f97316" stroke-width="2" fill="none" opacity=".6"/><path d="M44 46 Q47 38 50 46" stroke="#f97316" stroke-width="2" fill="none" opacity=".6"/><path d="M36 56 Q40 48 44 56" stroke="#fbbf24" stroke-width="2" fill="none" opacity=".5"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(239,68,68,.15)"/></svg>`,
        2: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="fEgg2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fbbf24"/><stop offset="100%" stop-color="#ef4444"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#fEgg2)"/><path d="M30 42 Q33 36 36 42" stroke="#f97316" stroke-width="2" fill="none" opacity=".6"/><path d="M44 46 Q47 38 50 46" stroke="#f97316" stroke-width="2" fill="none" opacity=".6"/><line x1="28" y1="48" x2="35" y2="58" stroke="#7f1d1d" stroke-width="2" opacity=".5"/><line x1="42" y1="40" x2="48" y2="52" stroke="#7f1d1d" stroke-width="2" opacity=".5"/><line x1="36" y1="62" x2="44" y2="55" stroke="#7f1d1d" stroke-width="1.5" opacity=".4"/><path d="M32 30 Q36 18 40 28 Q44 18 48 30" fill="#f97316" opacity=".7"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(239,68,68,.2)"/></svg>`,
        3: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="fPup" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fbbf24"/><stop offset="100%" stop-color="#ef4444"/></linearGradient></defs><ellipse cx="40" cy="56" rx="22" ry="20" fill="url(#fPup)"/><circle cx="40" cy="40" r="16" fill="#f97316"/><circle cx="34" cy="38" r="3" fill="#fff"/><circle cx="34" cy="38" r="1.5" fill="#1e293b"/><circle cx="46" cy="38" r="3" fill="#fff"/><circle cx="46" cy="38" r="1.5" fill="#1e293b"/><ellipse cx="40" cy="44" rx="2" ry="1.2" fill="#1e293b"/><path d="M36 46 Q40 49 44 46" stroke="#1e293b" stroke-width="1.2" fill="none"/><path d="M26 30 Q30 20 34 28" fill="#ef4444"/><path d="M46 28 Q50 20 54 30" fill="#ef4444"/><ellipse cx="30" cy="62" rx="5" ry="4" fill="#dc2626"/><ellipse cx="50" cy="62" rx="5" ry="4" fill="#dc2626"/><path d="M56 50 Q66 42 60 56 Q68 50 62 60" fill="#fbbf24" opacity=".8"/><ellipse cx="40" cy="78" rx="18" ry="3" fill="rgba(239,68,68,.12)"/></svg>`,
        4: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="fBeast" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fbbf24"/><stop offset="100%" stop-color="#dc2626"/></linearGradient></defs><ellipse cx="40" cy="60" rx="24" ry="22" fill="url(#fBeast)"/><circle cx="40" cy="38" r="18" fill="#ef4444"/><path d="M24 28 Q28 12 34 24" fill="#dc2626"/><path d="M46 24 Q52 12 56 28" fill="#dc2626"/><circle cx="33" cy="35" r="4" fill="#fff"/><circle cx="33" cy="35" r="2" fill="#1e293b"/><circle cx="47" cy="35" r="4" fill="#fff"/><circle cx="47" cy="35" r="2" fill="#1e293b"/><ellipse cx="40" cy="42" rx="2.5" ry="1.5" fill="#1e293b"/><path d="M34 46 Q40 50 46 46" stroke="#1e293b" stroke-width="1.5" fill="none"/><ellipse cx="28" cy="68" rx="6" ry="5" fill="#dc2626"/><ellipse cx="52" cy="68" rx="6" ry="5" fill="#dc2626"/><path d="M58 48 Q72 36 64 54 Q76 44 66 60 Q74 52 68 62" fill="#fbbf24" opacity=".85"/><path d="M22 48 Q8 36 16 54 Q4 44 14 60" fill="#fbbf24" opacity=".6"/><path d="M36 82 L40 90 L44 82" fill="#dc2626" opacity=".7"/><ellipse cx="40" cy="86" rx="22" ry="4" fill="rgba(239,68,68,.15)"/></svg>`,
        5: () => `<svg viewBox="0 0 90 110" class="pet-svg"><defs><linearGradient id="fGuard" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#fbbf24"/><stop offset="50%" stop-color="#ef4444"/><stop offset="100%" stop-color="#991b1b"/></linearGradient><filter id="fGlow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs><ellipse cx="45" cy="62" rx="28" ry="26" fill="url(#fGuard)" filter="url(#fGlow)"/><circle cx="45" cy="36" r="20" fill="#ef4444"/><path d="M27 24 Q32 4 39 20" fill="#dc2626"/><path d="M51 20 Q58 4 63 24" fill="#dc2626"/><path d="M45 16 Q45 6 45 16" fill="#fbbf24"/><circle cx="38" cy="33" r="4.5" fill="#fff"/><circle cx="38" cy="33" r="2.5" fill="#1e293b"/><circle cx="52" cy="33" r="4.5" fill="#fff"/><circle cx="52" cy="33" r="2.5" fill="#1e293b"/><ellipse cx="45" cy="40" rx="3" ry="1.8" fill="#1e293b"/><path d="M38 45 Q45 50 52 45" stroke="#1e293b" stroke-width="1.5" fill="none"/><path d="M17 50 Q3 34 12 54 Q0 40 10 58" fill="#fbbf24" opacity=".8"/><path d="M73 50 Q87 34 78 54 Q90 40 80 58" fill="#fbbf24" opacity=".8"/><path d="M30 80 Q28 96 34 88" fill="#991b1b"/><path d="M60 80 Q62 96 56 88" fill="#991b1b"/><path d="M62 56 Q78 48 70 62 Q82 52 74 66 Q80 58 76 68" fill="#fbbf24"/><path d="M28 56 Q12 48 20 62 Q8 52 16 66" fill="#fbbf24" opacity=".7"/><circle cx="45" cy="14" r="5" fill="#fbbf24" opacity=".6"/><ellipse cx="45" cy="96" rx="26" ry="5" fill="rgba(239,68,68,.18)"/></svg>`,
    };

    // ────────── WATER ──────────
    const water = {
        1: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="wEgg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#67e8f9"/><stop offset="100%" stop-color="#3b82f6"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#wEgg)"/><ellipse cx="40" cy="55" rx="28" ry="35" fill="none" stroke="#2563eb" stroke-width="1.5" opacity=".3"/><circle cx="30" cy="45" r="3" fill="#bfdbfe" opacity=".6"/><circle cx="48" cy="50" r="4" fill="#bfdbfe" opacity=".5"/><circle cx="36" cy="60" r="2.5" fill="#bfdbfe" opacity=".5"/><path d="M26 52 Q32 48 38 52 Q44 48 50 52" stroke="#93c5fd" stroke-width="1.5" fill="none" opacity=".4"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(59,130,246,.15)"/></svg>`,
        2: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="wEgg2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#67e8f9"/><stop offset="100%" stop-color="#3b82f6"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#wEgg2)"/><circle cx="30" cy="45" r="3" fill="#bfdbfe" opacity=".6"/><circle cx="48" cy="50" r="4" fill="#bfdbfe" opacity=".5"/><line x1="28" y1="48" x2="35" y2="58" stroke="#1e3a5f" stroke-width="2" opacity=".5"/><line x1="42" y1="40" x2="48" y2="52" stroke="#1e3a5f" stroke-width="2" opacity=".5"/><line x1="36" y1="62" x2="44" y2="55" stroke="#1e3a5f" stroke-width="1.5" opacity=".4"/><path d="M30 28 Q34 20 36 28 M42 26 Q46 18 48 26" fill="#67e8f9" opacity=".7"/><circle cx="33" cy="24" r="2" fill="#bfdbfe" opacity=".6"/><circle cx="47" cy="22" r="1.5" fill="#bfdbfe" opacity=".5"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(59,130,246,.2)"/></svg>`,
        3: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="wPup" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#67e8f9"/><stop offset="100%" stop-color="#3b82f6"/></linearGradient></defs><ellipse cx="40" cy="58" rx="24" ry="18" fill="url(#wPup)"/><ellipse cx="40" cy="58" rx="24" ry="18" fill="none" stroke="#2563eb" stroke-width="1" opacity=".3"/><circle cx="40" cy="40" r="16" fill="#60a5fa"/><circle cx="34" cy="38" r="3" fill="#fff"/><circle cx="34" cy="38" r="1.5" fill="#1e293b"/><circle cx="46" cy="38" r="3" fill="#fff"/><circle cx="46" cy="38" r="1.5" fill="#1e293b"/><ellipse cx="40" cy="44" rx="2" ry="1.2" fill="#1e293b"/><path d="M36 46 Q40 49 44 46" stroke="#1e293b" stroke-width="1.2" fill="none"/><path d="M20 52 Q16 48 18 56 Q14 52 18 58" fill="#93c5fd" opacity=".5"/><path d="M60 52 Q64 48 62 56 Q66 52 62 58" fill="#93c5fd" opacity=".5"/><ellipse cx="30" cy="70" rx="5" ry="4" fill="#2563eb"/><ellipse cx="50" cy="70" rx="5" ry="4" fill="#2563eb"/><ellipse cx="40" cy="78" rx="18" ry="3" fill="rgba(59,130,246,.12)"/></svg>`,
        4: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="wBeast" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#67e8f9"/><stop offset="100%" stop-color="#2563eb"/></linearGradient></defs><ellipse cx="40" cy="60" rx="26" ry="22" fill="url(#wBeast)"/><circle cx="40" cy="38" r="18" fill="#3b82f6"/><path d="M24 30 Q28 18 34 28" fill="#2563eb"/><path d="M46 28 Q52 18 56 30" fill="#2563eb"/><circle cx="33" cy="35" r="4" fill="#fff"/><circle cx="33" cy="35" r="2" fill="#1e293b"/><circle cx="47" cy="35" r="4" fill="#fff"/><circle cx="47" cy="35" r="2" fill="#1e293b"/><ellipse cx="40" cy="42" rx="2.5" ry="1.5" fill="#1e293b"/><path d="M34 46 Q40 50 46 46" stroke="#1e293b" stroke-width="1.5" fill="none"/><ellipse cx="28" cy="68" rx="6" ry="5" fill="#2563eb"/><ellipse cx="52" cy="68" rx="6" ry="5" fill="#2563eb"/><path d="M16 48 Q6 42 14 56 Q4 50 14 60" fill="#93c5fd" opacity=".6"/><path d="M64 48 Q74 42 66 56 Q76 50 66 60" fill="#93c5fd" opacity=".6"/><circle cx="20" cy="44" r="2" fill="#bfdbfe" opacity=".5"/><circle cx="62" cy="42" r="2.5" fill="#bfdbfe" opacity=".5"/><path d="M36 82 Q40 88 44 82" fill="#2563eb" opacity=".6"/><ellipse cx="40" cy="86" rx="22" ry="4" fill="rgba(59,130,246,.15)"/></svg>`,
        5: () => `<svg viewBox="0 0 90 110" class="pet-svg"><defs><linearGradient id="wGuard" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#67e8f9"/><stop offset="50%" stop-color="#3b82f6"/><stop offset="100%" stop-color="#1e3a8a"/></linearGradient><filter id="wGlow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs><ellipse cx="45" cy="62" rx="28" ry="26" fill="url(#wGuard)" filter="url(#wGlow)"/><circle cx="45" cy="36" r="20" fill="#3b82f6"/><path d="M27 26 Q32 10 39 22" fill="#2563eb"/><path d="M51 22 Q58 10 63 26" fill="#2563eb"/><circle cx="38" cy="33" r="4.5" fill="#fff"/><circle cx="38" cy="33" r="2.5" fill="#1e293b"/><circle cx="52" cy="33" r="4.5" fill="#fff"/><circle cx="52" cy="33" r="2.5" fill="#1e293b"/><ellipse cx="45" cy="40" rx="3" ry="1.8" fill="#1e293b"/><path d="M38 45 Q45 50 52 45" stroke="#1e293b" stroke-width="1.5" fill="none"/><path d="M15 50 Q2 38 12 56 Q0 44 10 60" fill="#93c5fd" opacity=".7"/><path d="M75 50 Q88 38 78 56 Q90 44 80 60" fill="#93c5fd" opacity=".7"/><circle cx="10" cy="46" r="3" fill="#bfdbfe" opacity=".5"/><circle cx="80" cy="44" r="3" fill="#bfdbfe" opacity=".5"/><circle cx="20" cy="56" r="2" fill="#bfdbfe" opacity=".4"/><circle cx="70" cy="54" r="2" fill="#bfdbfe" opacity=".4"/><path d="M30 80 Q28 96 34 88" fill="#1e3a8a"/><path d="M60 80 Q62 96 56 88" fill="#1e3a8a"/><path d="M45 14 L42 8 L45 12 L48 8 Z" fill="#67e8f9" opacity=".7"/><ellipse cx="45" cy="96" rx="26" ry="5" fill="rgba(59,130,246,.18)"/></svg>`,
    };

    // ────────── GRASS ──────────
    const grass = {
        1: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="gEgg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a3e635"/><stop offset="100%" stop-color="#22c55e"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#gEgg)"/><ellipse cx="40" cy="55" rx="28" ry="35" fill="none" stroke="#15803d" stroke-width="1.5" opacity=".3"/><path d="M30 48 Q34 42 38 48 L34 44 Z" fill="#15803d" opacity=".35"/><path d="M42 52 Q46 46 50 52 L46 48 Z" fill="#15803d" opacity=".35"/><path d="M34 62 Q38 56 42 62 L38 58 Z" fill="#15803d" opacity=".3"/><path d="M36 25 Q40 14 44 25" fill="#4ade80" opacity=".6"/><path d="M38 22 L40 12 L42 22" fill="#22c55e" opacity=".5"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(34,197,94,.15)"/></svg>`,
        2: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="gEgg2" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a3e635"/><stop offset="100%" stop-color="#22c55e"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#gEgg2)"/><path d="M30 48 Q34 42 38 48 L34 44 Z" fill="#15803d" opacity=".35"/><path d="M42 52 Q46 46 50 52 L46 48 Z" fill="#15803d" opacity=".35"/><line x1="28" y1="48" x2="35" y2="58" stroke="#14532d" stroke-width="2" opacity=".5"/><line x1="42" y1="40" x2="48" y2="52" stroke="#14532d" stroke-width="2" opacity=".5"/><line x1="36" y1="62" x2="44" y2="55" stroke="#14532d" stroke-width="1.5" opacity=".4"/><path d="M32 22 Q36 8 40 22" fill="#4ade80" opacity=".7"/><path d="M40 22 Q44 8 48 22" fill="#22c55e" opacity=".7"/><path d="M36 18 L38 6 L40 18" fill="#15803d" opacity=".5"/><circle cx="38" cy="10" r="2" fill="#a3e635" opacity=".6"/><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(34,197,94,.2)"/></svg>`,
        3: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="gPup" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a3e635"/><stop offset="100%" stop-color="#22c55e"/></linearGradient></defs><ellipse cx="40" cy="58" rx="22" ry="18" fill="url(#gPup)"/><circle cx="40" cy="40" r="16" fill="#4ade80"/><circle cx="34" cy="38" r="3" fill="#fff"/><circle cx="34" cy="38" r="1.5" fill="#1e293b"/><circle cx="46" cy="38" r="3" fill="#fff"/><circle cx="46" cy="38" r="1.5" fill="#1e293b"/><ellipse cx="40" cy="44" rx="2" ry="1.2" fill="#1e293b"/><path d="M36 46 Q40 49 44 46" stroke="#1e293b" stroke-width="1.2" fill="none"/><path d="M26 32 Q28 24 32 30" fill="#15803d"/><path d="M48 30 Q52 24 54 32" fill="#15803d"/><path d="M22 52 Q18 46 20 56 L16 50" fill="#a3e635" opacity=".5"/><path d="M58 52 Q62 46 60 56 L64 50" fill="#a3e635" opacity=".5"/><ellipse cx="30" cy="70" rx="5" ry="4" fill="#16a34a"/><ellipse cx="50" cy="70" rx="5" ry="4" fill="#16a34a"/><ellipse cx="40" cy="78" rx="18" ry="3" fill="rgba(34,197,94,.12)"/></svg>`,
        4: () => `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="gBeast" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a3e635"/><stop offset="100%" stop-color="#15803d"/></linearGradient></defs><ellipse cx="40" cy="60" rx="26" ry="22" fill="url(#gBeast)"/><circle cx="40" cy="38" r="18" fill="#22c55e"/><path d="M24 30 Q28 16 34 26" fill="#15803d"/><path d="M46 26 Q52 16 56 30" fill="#15803d"/><circle cx="33" cy="35" r="4" fill="#fff"/><circle cx="33" cy="35" r="2" fill="#1e293b"/><circle cx="47" cy="35" r="4" fill="#fff"/><circle cx="47" cy="35" r="2" fill="#1e293b"/><ellipse cx="40" cy="42" rx="2.5" ry="1.5" fill="#1e293b"/><path d="M34 46 Q40 50 46 46" stroke="#1e293b" stroke-width="1.5" fill="none"/><ellipse cx="28" cy="68" rx="6" ry="5" fill="#15803d"/><ellipse cx="52" cy="68" rx="6" ry="5" fill="#15803d"/><path d="M16 44 Q10 36 14 50 L8 42 Q6 50 12 54" fill="#a3e635" opacity=".6"/><path d="M64 44 Q70 36 66 50 L72 42 Q74 50 68 54" fill="#a3e635" opacity=".6"/><path d="M38 20 L36 10 L40 18 L44 10 L42 20" fill="#4ade80" opacity=".7"/><path d="M36 82 Q40 88 44 82" fill="#15803d" opacity=".6"/><ellipse cx="40" cy="86" rx="22" ry="4" fill="rgba(34,197,94,.15)"/></svg>`,
        5: () => `<svg viewBox="0 0 90 110" class="pet-svg"><defs><linearGradient id="gGuard" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#a3e635"/><stop offset="50%" stop-color="#22c55e"/><stop offset="100%" stop-color="#14532d"/></linearGradient><filter id="gGlow"><feGaussianBlur stdDeviation="3" result="blur"/><feMerge><feMergeNode in="blur"/><feMergeNode in="SourceGraphic"/></feMerge></filter></defs><ellipse cx="45" cy="62" rx="28" ry="26" fill="url(#gGuard)" filter="url(#gGlow)"/><circle cx="45" cy="36" r="20" fill="#22c55e"/><path d="M27 26 Q32 8 39 22" fill="#15803d"/><path d="M51 22 Q58 8 63 26" fill="#15803d"/><circle cx="38" cy="33" r="4.5" fill="#fff"/><circle cx="38" cy="33" r="2.5" fill="#1e293b"/><circle cx="52" cy="33" r="4.5" fill="#fff"/><circle cx="52" cy="33" r="2.5" fill="#1e293b"/><ellipse cx="45" cy="40" rx="3" ry="1.8" fill="#1e293b"/><path d="M38 45 Q45 50 52 45" stroke="#1e293b" stroke-width="1.5" fill="none"/><path d="M15 46 Q4 36 12 52 Q0 44 10 56" fill="#a3e635" opacity=".7"/><path d="M75 46 Q86 36 78 52 Q90 44 80 56" fill="#a3e635" opacity=".7"/><path d="M10 50 L6 42 L12 48" fill="#4ade80" opacity=".5"/><path d="M80 50 L84 42 L78 48" fill="#4ade80" opacity=".5"/><path d="M30 80 Q28 96 34 88" fill="#14532d"/><path d="M60 80 Q62 96 56 88" fill="#14532d"/><path d="M40 12 L36 2 L40 10 L45 0 L45 12 L50 2 L46 12" fill="#a3e635" opacity=".65"/><circle cx="38" cy="4" r="2" fill="#4ade80" opacity=".5"/><circle cx="50" cy="2" r="1.5" fill="#4ade80" opacity=".4"/><ellipse cx="45" cy="96" rx="26" ry="5" fill="rgba(34,197,94,.18)"/></svg>`,
    };

    /* ═══════════════════════════════════════════════════════
       SPRITE MAPS
       ═══════════════════════════════════════════════════════ */
    const SPRITES = { fire, water, grass };

    /* ═══════════════════════════════════════════════════════
       FALLBACK (no team chosen yet — neutral grey egg)
       ═══════════════════════════════════════════════════════ */
    function neutralEgg() {
        return `<svg viewBox="0 0 80 100" class="pet-svg"><defs><linearGradient id="nEgg" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#e2e8f0"/><stop offset="100%" stop-color="#94a3b8"/></linearGradient></defs><ellipse cx="40" cy="55" rx="28" ry="35" fill="url(#nEgg)"/><ellipse cx="40" cy="55" rx="28" ry="35" fill="none" stroke="#64748b" stroke-width="1.5" opacity=".3"/><text x="40" y="60" text-anchor="middle" font-size="18" fill="#64748b">?</text><ellipse cx="40" cy="88" rx="20" ry="4" fill="rgba(100,116,139,.12)"/></svg>`;
    }

    /* ═══════════════════════════════════════════════════════
       PUBLIC API
       ═══════════════════════════════════════════════════════ */
    window.PetSprites = {
        /**
         * Get SVG HTML for a team + stage combo.
         * @param {string} team  — 'fire' | 'water' | 'grass'
         * @param {number} stage — 1..5
         * @returns {string} inline SVG markup
         */
        get: function (team, stage) {
            const map = SPRITES[team];
            if (!map) return neutralEgg();
            const fn = map[stage];
            return fn ? fn() : neutralEgg();
        },

        /** All 5 stages for a team. */
        getEvo: function (team) {
            const out = {};
            for (let s = 1; s <= 5; s++) out[s] = this.get(team, s);
            return out;
        },

        /** Primary colour hex for a team. */
        teamColor: function (team) {
            return PALETTES[team] ? PALETTES[team].pri : '#94a3b8';
        },

        /** Glow colour for a team. */
        teamGlow: function (team) {
            return PALETTES[team] ? PALETTES[team].glow : 'rgba(100,116,139,.2)';
        },

        /** Full palette object. */
        palette: function (team) {
            return PALETTES[team] || PALETTES.fire;
        },

        /** Stage name. */
        stageName: function (team, stage) {
            const names = STAGE_NAMES[team];
            return names ? (names[stage] || 'Unknown') : 'Unknown';
        },

        /** Stage description. */
        stageDesc: function (team, stage) {
            const descs = STAGE_DESCS[team];
            return descs ? (descs[stage] || '') : '';
        },

        /** Mini icon (smaller viewBox, for timeline/nav). */
        getMini: function (team, stage) {
            // Returns the same SVG but the CSS class .pet-svg-mini sizes it
            const svg = this.get(team, stage);
            return svg.replace('class="pet-svg"', 'class="pet-svg pet-svg-mini"');
        },
    };

})();
