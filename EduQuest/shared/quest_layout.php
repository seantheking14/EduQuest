<?php
/**
 * quest_layout.php
 * EduQuest — Quest-World Student Frontend
 *
 * Reusable layout wrapper for student-facing pages.
 * Usage — wrap main page content:
 *
 *   <?php require_once '/path/to/shared/quest_layout.php'; // top ?>
 *   ... page content ...
 *   <?php require_once '/path/to/shared/quest_layout_end.php'; // bottom ?>
 *
 * Or inline:
 *   echo quest_layout_open();
 *   ... content ...
 *   echo quest_layout_close();
 *
 * This file outputs the OPENING wrapper tag.
 * Include quest_layout_end.php (or echo </div>) to close it.
 */

/**
 * Render the opening quest content wrapper div.
 *
 * @param string $extraClass  Optional additional CSS classes.
 * @return void
 */
function quest_layout_open(string $extraClass = ''): void {
    $class = trim('quest-content-wrapper ' . $extraClass);
    echo '<div class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '">' . "\n";
}

/**
 * Render the closing tag for the quest content wrapper.
 *
 * @return void
 */
function quest_layout_close(): void {
    echo '</div><!-- /.quest-content-wrapper -->' . "\n";
}

/*
 * Auto-output the opening tag if this file is included directly
 * (not via require_once inside a function call). Detect by checking
 * whether quest_layout_open() has already been called by the caller.
 * Simple convention: call quest_layout_open() manually, or just
 * include this file and it opens the wrapper automatically.
 */
quest_layout_open();
?>
