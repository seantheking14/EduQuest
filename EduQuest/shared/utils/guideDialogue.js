/**
 * guideDialogue.js — All dialogue pools for the virtual guide character.
 * Organized by mood/trigger state. Each pool has 3+ options.
 * All language is warm, encouraging, ADHD-friendly. No negative framing.
 */
window.GUIDE_DIALOGUE = {
    /* ── Quiz / Game States ── */
    neutral: [
        "You've got this! Take your time! ⭐",
        "Read carefully — I believe in you! 💛",
        "Let's give this our best shot! 🌟",
        "I'm right here cheering for you! 💪",
        "You're going to do great! 🎯",
    ],
    encouraging: [
        "Almost there, keep thinking! 🧠",
        "I can see you're working hard! 💪",
        "Take a deep breath, you're doing great! 🌈",
        "You're amazing for trying! Keep it up! ⭐",
        "I love how focused you are! 🎯",
    ],
    timeWarning: [
        "Go with your gut feeling! ⚡",
        "Trust yourself — pick your best answer! 💫",
        "You're so close! Just a little more! 🌟",
        "Deep breath — you've got this! 💛",
    ],
    celebrating: [
        "YES! That's amazing! 🎉",
        "You did it! I knew you could! 🏆",
        "Superstar! That was perfect! ⭐",
        "Incredible work! You're on fire! 🔥",
        "WOW! Look at you go! 🌟",
    ],
    hinting: [
        "Hmm, let's think about this one more time! 🤔",
        "So close! Here's a little clue for you! 💡",
        "Let's look at this together! 🌟",
        "Good try! Let me give you a hint! 💛",
        "You're learning! Check out this clue! ✨",
    ],
    comforting: [
        "Oops! Time flew by! Let's try together! 💛",
        "That was a tricky one! Here's a little hint! 🤔",
        "No worries at all! Let's look at this again! 🌈",
        "Every try makes you stronger! 💪",
        "You're doing so well — let's keep going! ⭐",
    ],
    lastAttempt: [
        "You gave it your best! Let's see the answer and learn together! 💛",
        "Every try makes you stronger! Let's see the answer! 🌈",
        "You worked so hard! Here's the answer so we can learn! ⭐",
        "That was tough! Let's learn the answer together! 🤝",
    ],

    /* ── Lesson States ── */
    lessonStart: [
        "Welcome! Let's learn something new today! 📚",
        "Hey! I'm excited to learn with you! 🌟",
        "Adventure time! Let's discover something cool! ⭐",
        "Ready to learn? I'll be right here with you! 💛",
    ],
    lessonProgress: [
        "Great job keeping up! You're doing amazing! 🌟",
        "Look at you go! Keep reading! 💪",
        "Awesome! You're learning so much! ⭐",
        "You're doing fantastic! Keep it up! 🎯",
    ],
    lessonHalfway: [
        "Halfway there! You're such a hard worker! 💪",
        "Look how far you've come! Amazing! 🌟",
        "50% done — you're crushing it! 🎉",
        "You're halfway through! So proud of you! ⭐",
    ],
    lessonEnd: [
        "You finished the lesson! That was incredible! 🎉",
        "WOW! You completed everything! 🏆",
        "Amazing work! You're a learning superstar! ⭐",
        "You did it! I'm so proud of you! 💛",
    ],
    lessonIdle: [
        "Hey! Take your time, I'm right here! 💛",
        "No rush! Whenever you're ready, I'm with you! 🌈",
        "You're doing so well! Keep going when you're ready! ⭐",
        "I'm still here with you — no hurry! 😊",
        "Take a nice deep breath! 🌟",
    ],
    lessonBeforeQuiz: [
        "Ready to show what you learned? I'll be with you! ⭐",
        "Quiz time! I know you'll do great! 💪",
        "You learned so much — let's put it to the test! 🎯",
        "Time to shine! You've got this! 🌟",
    ],
};

/**
 * Guide emotional expressions by mood
 */
window.GUIDE_EXPRESSIONS = {
    neutral:     '😊',
    encouraging: '🤩',
    hinting:     '🤔',
    celebrating: '🎉',
    timeWarning: '😮',
    comforting:  '🥰',
    lastAttempt: '🥰',
    lessonStart: '😊',
    lessonProgress: '🤩',
    lessonHalfway: '💪',
    lessonEnd:   '🎉',
    lessonIdle:  '😊',
    lessonBeforeQuiz: '🤩',
};
