<?php
/**
 * pet_display.php — Reusable pet card component
 *
 * Usage (include from any PHP page):
 *   include __DIR__ . '/../../shared/components/pet_display.php';
 *   echo renderPetCard('fire', 2, 'Maria', '../../EDUQUEST/assets/pets/');
 *
 * Or call with named variables set before including:
 *   $petTeam  = 'water'; $petStage = 1; $petStudent = 'Juan';
 *   include '.../pet_display.php';
 */

/**
 * Render a pet card HTML string.
 *
 * @param string $team         'fire' | 'water' | 'grass'
 * @param int    $stage        0 = Egg, 1 = Baby, 2 = Young, 3 = Adult
 * @param string $studentName  Student's display name
 * @param string $assetsBase   Base URL to pets/ folder (trailing slash required)
 * @return string              HTML markup for the .pet-card element
 */
function renderPetCard(string $team, int $stage, string $studentName = '', string $assetsBase = '../../EDUQUEST/assets/pets/'): string
{
    // Clamp stage
    $stage = max(0, min(3, $stage));

    // Name tables
    $stageNames = ['egg', 'baby', 'young', 'adult'];

    $petNames = [
        'fire'  => ['Fire Egg',  'Iggy',   'Blazeback', 'Thornflare'],
        'water' => ['Water Egg', 'Bubbles', 'Shellby',   'Tidalback'],
        'grass' => ['Grass Egg', 'Sprout',  'Twigster',  'Vinespark'],
    ];

    $teamEmojis = [
        'fire'  => '🔥',
        'water' => '💧',
        'grass' => '🌿',
    ];

    $teamGlowColors = [
        'fire'  => 'rgba(192,57,43,0.55)',
        'water' => 'rgba(26,188,156,0.5)',
        'grass' => 'rgba(39,174,96,0.5)',
    ];

    $teamAccentColors = [
        'fire'  => '#e74c3c',
        'water' => '#1abc9c',
        'grass' => '#27ae60',
    ];

    // Guard against unknown team
    if (!isset($petNames[$team])) {
        $team = 'fire';
    }

    $petName    = $petNames[$team][$stage];
    $emoji      = $teamEmojis[$team];
    $glow       = $teamGlowColors[$team];
    $accent     = $teamAccentColors[$team];
    $stageName  = $stageNames[$stage];
    $imgSrc     = htmlspecialchars($assetsBase . $team . '/' . $stageName . '.svg', ENT_QUOTES, 'UTF-8');
    $petNameHtml = htmlspecialchars($petName, ENT_QUOTES, 'UTF-8');
    $studentHtml = htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8');
    $teamHtml    = htmlspecialchars($team, ENT_QUOTES, 'UTF-8');
    $tooltip     = $studentName
        ? "{$petName} — {$studentName}'s companion"
        : $petName;
    $tooltipHtml = htmlspecialchars($tooltip, ENT_QUOTES, 'UTF-8');

    $boxShadow   = "0 0 14px 4px {$glow}";
    $borderColor = $accent;

    return <<<HTML
<div class="pet-card" data-team="{$teamHtml}" data-stage="{$stage}"
     title="{$tooltipHtml}"
     style="border-color:{$borderColor}; box-shadow:{$boxShadow};">
    <div class="pet-img-wrap">
        <img class="pet-img"
             src="{$imgSrc}"
             alt="{$petNameHtml}"
             loading="lazy"
             draggable="false">
    </div>
    <div class="pet-name-row">
        <span class="pet-team-badge">{$emoji}</span>
        <span class="pet-name">{$petNameHtml}</span>
    </div>
</div>
HTML;
}
?>
