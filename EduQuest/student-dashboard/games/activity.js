/* ═══════════════════════════════════════════════════════════════
   Learning Activities — Game Engine
   SPED-friendly interactive activities for Math, English, Self Care
   ═══════════════════════════════════════════════════════════════ */
(() => {
    'use strict';

    /* ══════════════════════════════════════════════════════════
       ACTIVITY DATA — organised by subject
       Each activity has: id, subject, icon, title, desc,
       type (sort-order | classify | compare | choose | build-word),
       instruction (shown during game), and rounds[]
       ══════════════════════════════════════════════════════════ */

    const BANK = [

        /* ────────────── MATH ────────────── */
        {
            id: 'math-sort-asc',
            subject: 'math',
            icon: '🔢',
            title: 'Arrange Numbers ↑',
            desc: 'Put numbers in order from smallest to biggest!',
            type: 'sort-order',
            instruction: 'Tap numbers from SMALLEST to BIGGEST',
            config: { direction: 'asc' },
            rounds: [
                { items: [5, 2, 8, 1, 3] },
                { items: [12, 7, 15, 3, 9] },
                { items: [25, 14, 31, 8, 19] },
                { items: [56, 23, 78, 12, 45] },
                { items: [90, 34, 67, 11, 52] },
                { items: [99, 100, 50, 75, 25] },
            ]
        },
        {
            id: 'math-sort-desc',
            subject: 'math',
            icon: '🔢',
            title: 'Arrange Numbers ↓',
            desc: 'Put numbers in order from biggest to smallest!',
            type: 'sort-order',
            instruction: 'Tap numbers from BIGGEST to SMALLEST',
            config: { direction: 'desc' },
            rounds: [
                { items: [4, 9, 1, 7, 3] },
                { items: [18, 5, 22, 10, 14] },
                { items: [35, 12, 47, 28, 6] },
                { items: [63, 81, 29, 55, 40] },
                { items: [100, 45, 72, 88, 33] },
                { items: [250, 100, 400, 50, 300] },
            ]
        },
        {
            id: 'math-compare',
            subject: 'math',
            icon: '⚖️',
            title: 'Compare Numbers',
            desc: 'Choose <, = or > to compare two numbers!',
            type: 'compare',
            instruction: 'Which symbol goes between the numbers?',
            rounds: [
                { left: 3, right: 7, answer: '<' },
                { left: 12, right: 5, answer: '>' },
                { left: 8, right: 8, answer: '=' },
                { left: 15, right: 23, answer: '<' },
                { left: 50, right: 35, answer: '>' },
                { left: 44, right: 44, answer: '=' },
                { left: 88, right: 91, answer: '<' },
                { left: 100, right: 78, answer: '>' },
            ]
        },
        {
            id: 'math-ordinal',
            subject: 'math',
            icon: '🏅',
            title: 'Ordinal Numbers',
            desc: 'Learn 1st, 2nd, 3rd and more!',
            type: 'choose',
            instruction: 'Pick the correct ordinal number!',
            rounds: [
                { emoji: '🥇', question: 'The winner of a race is in _____ place.', options: ['1st', '2nd', '3rd', '4th'], answer: 0 },
                { emoji: '🥈', question: 'The one after 1st is _____.', options: ['3rd', '2nd', '4th', '1st'], answer: 1 },
                { emoji: '🥉', question: 'If you are number 3 in line, you are _____.', options: ['2nd', '4th', '3rd', '1st'], answer: 2 },
                { emoji: '🔢', question: 'What is the ordinal for 5?', options: ['4th', '5th', '6th', '3rd'], answer: 1 },
                { emoji: '📅', question: 'Monday is the _____ day of the school week.', options: ['2nd', '3rd', '1st', '4th'], answer: 2 },
                { emoji: '🏃', question: '10th means position number _____.', options: ['9', '10', '11', '8'], answer: 1 },
            ]
        },
        {
            id: 'math-coins',
            subject: 'math',
            icon: '💰',
            title: 'Coins & Peso Bills',
            desc: 'Learn the value of Philippine coins and bills!',
            type: 'choose',
            instruction: 'Pick the correct value!',
            rounds: [
                { emoji: '🪙', question: 'The SMALLEST Philippine coin is worth _____.', options: ['₱1', '₱5', '25 centavos', '10 centavos'], answer: 2 },
                { emoji: '🪙', question: 'A ₱5 coin is worth _____ ₱1 coins.', options: ['3', '5', '10', '2'], answer: 1 },
                { emoji: '💵', question: 'The ₱20 bill has this color:', options: ['Blue', 'Orange', 'Green', 'Red'], answer: 1 },
                { emoji: '💵', question: 'Which bill is worth the most?', options: ['₱50', '₱100', '₱500', '₱200'], answer: 2 },
                { emoji: '🪙', question: '₱10 coin = how many ₱5 coins?', options: ['1', '2', '3', '5'], answer: 1 },
                { emoji: '💰', question: '₱100 bill = how many ₱20 bills?', options: ['3', '4', '5', '10'], answer: 2 },
                { emoji: '💵', question: 'What color is the ₱1000 bill?', options: ['Blue', 'Orange', 'Green', 'Red'], answer: 0 },
                { emoji: '🛒', question: 'You buy candy for ₱8. You pay ₱10. Your change is:', options: ['₱1', '₱2', '₱3', '₱5'], answer: 1 },
            ]
        },
        {
            id: 'math-numwords',
            subject: 'math',
            icon: '📝',
            title: 'Number Words',
            desc: 'Match numbers to their word names!',
            type: 'choose',
            instruction: 'What is the correct word for this number?',
            rounds: [
                { emoji: '1️⃣', question: 'What word is the number 15?', options: ['Fifty', 'Fifteen', 'Five', 'Fifty-one'], answer: 1 },
                { emoji: '2️⃣', question: 'What word is the number 40?', options: ['Fourteen', 'Four', 'Forty', 'Four hundred'], answer: 2 },
                { emoji: '3️⃣', question: 'What word is the number 100?', options: ['One hundred', 'Ten', 'One thousand', 'Eleven'], answer: 0 },
                { emoji: '4️⃣', question: 'What word is the number 250?', options: ['Twenty-five', 'Two hundred fifty', 'Two thousand', 'Two fifty'], answer: 1 },
                { emoji: '5️⃣', question: 'What word is the number 500?', options: ['Fifty', 'Five thousand', 'Five hundred', 'Fifteen'], answer: 2 },
                { emoji: '6️⃣', question: 'What number is "Three hundred"?', options: ['30', '300', '3000', '33'], answer: 1 },
            ]
        },

        /* ────────────── ENGLISH ────────────── */
        {
            id: 'eng-build-cvc',
            subject: 'english',
            icon: '🔤',
            title: 'Build CVC Words (/Ii/)',
            desc: 'Build short words with the /Ii/ sound!',
            type: 'build-word',
            instruction: 'Tap the letters to build the word!',
            rounds: [
                { word: 'SIT',  hint: '🪑 You do this on a chair', extras: ['A', 'O'] },
                { word: 'PIN',  hint: '📌 A sharp pointy thing',   extras: ['A', 'E'] },
                { word: 'DIG',  hint: '⛏️ What you do in the sand', extras: ['O', 'U'] },
                { word: 'BIG',  hint: '🐘 Not small — very ___!',  extras: ['A', 'U'] },
                { word: 'HIT',  hint: '🏏 To strike or tap',       extras: ['O', 'A'] },
                { word: 'LIP',  hint: '👄 Part of your mouth',     extras: ['O', 'A'] },
                { word: 'MIX',  hint: '🥣 Stir things together',   extras: ['A', 'U'] },
                { word: 'WIG',  hint: '💇 Fake hair you can wear', extras: ['O', 'A'] },
                { word: 'FIT',  hint: '👕 The shirt is the right size. It ___s!', extras: ['O', 'A'] },
                { word: 'RIP',  hint: '📄 To tear paper apart',    extras: ['O', 'U'] },
            ]
        },
        {
            id: 'eng-read-cvc',
            subject: 'english',
            icon: '📖',
            title: 'Read /Ii/ Words',
            desc: 'Match the short /Ii/ word to its picture!',
            type: 'choose',
            instruction: 'Which word matches the picture?',
            rounds: [
                { emoji: '🦈', question: 'The sharp triangle sticking up on a shark\'s back is its ___:', options: ['fan', 'fin', 'fun', 'fen'], answer: 1 },
                { emoji: '🪣', question: 'You put things ___ the box.', options: ['on', 'in', 'an', 'un'], answer: 1 },
                { emoji: '🐷', question: 'A farm animal that says oink!', options: ['peg', 'pug', 'pig', 'pag'], answer: 2 },
                { emoji: '6️⃣', question: 'How do you spell the number 6?', options: ['sik', 'sax', 'sox', 'six'], answer: 3 },
                { emoji: '�', question: 'A bag or box with all the tools or supplies you need packed together is a ___:', options: ['kit', 'kat', 'kut', 'kite'], answer: 0 },
                { emoji: '🏊', question: 'Fish use a ___ to swim.', options: ['fan', 'fun', 'fin', 'fen'], answer: 2 },
                { emoji: '🤫', question: 'A baby sleeps in a ___.', options: ['crab', 'crib', 'crub', 'creb'], answer: 1 },
                { emoji: '💧', question: 'Water goes ___ ___, ___ ___. (small drops)', options: ['dip', 'dap', 'dup', 'dep'], answer: 0 },
            ]
        },
        {
            id: 'eng-sentences',
            subject: 'english',
            icon: '✏️',
            title: '/Ii/ Sentences',
            desc: 'Complete the sentence with the right word!',
            type: 'choose',
            instruction: 'Pick the word that completes the sentence!',
            rounds: [
                { emoji: '📝', question: 'The cat can ___ on the mat.', options: ['sat', 'sit', 'set', 'sot'], answer: 1 },
                { emoji: '📝', question: 'I ___ the ball very hard.', options: ['hat', 'hot', 'hit', 'hut'], answer: 2 },
                { emoji: '📝', question: 'Mom put a ___ in her hair.', options: ['pan', 'pen', 'pin', 'pun'], answer: 2 },
                { emoji: '📝', question: 'The toy is ___ the box.', options: ['on', 'an', 'in', 'un'], answer: 2 },
                { emoji: '📝', question: 'Dad will ___ a hole in the garden.', options: ['dog', 'dug', 'dig', 'dag'], answer: 2 },
                { emoji: '📝', question: 'The glass has a ___ on the side.', options: ['lip', 'lap', 'lop', 'lep'], answer: 0 },
            ]
        },

        /* ────────────── SELF CARE ────────────── */
        {
            id: 'sc-living',
            subject: 'selfcare',
            icon: '🌱',
            title: 'Living or Non-Living?',
            desc: 'Sort things into Living or Non-Living!',
            type: 'classify',
            instruction: 'Is this LIVING or NON-LIVING?',
            config: {
                categories: [
                    { id: 'living',    label: '🌱 Living',     color: '#10b981' },
                    { id: 'nonliving', label: '🪨 Non-Living', color: '#6b7280' }
                ]
            },
            rounds: [
                { emoji: '🐕', label: 'Dog',       answer: 'living' },
                { emoji: '🪨', label: 'Rock',      answer: 'nonliving' },
                { emoji: '🌳', label: 'Tree',      answer: 'living' },
                { emoji: '🪑', label: 'Chair',     answer: 'nonliving' },
                { emoji: '🐟', label: 'Fish',      answer: 'living' },
                { emoji: '📱', label: 'Phone',     answer: 'nonliving' },
                { emoji: '🌻', label: 'Flower',    answer: 'living' },
                { emoji: '📚', label: 'Book',      answer: 'nonliving' },
                { emoji: '🐛', label: 'Caterpillar', answer: 'living' },
                { emoji: '💡', label: 'Light bulb', answer: 'nonliving' },
            ]
        },
        {
            id: 'sc-weather',
            subject: 'selfcare',
            icon: '🌤️',
            title: 'Kinds of Weather',
            desc: 'Identify the different kinds of weather!',
            type: 'choose',
            instruction: 'What kind of weather is this?',
            rounds: [
                { emoji: '☀️', question: 'The sun is shining bright. What weather is this?', options: ['Rainy', 'Sunny', 'Stormy', 'Snowy'], answer: 1 },
                { emoji: '🌧️', question: 'Water drops from the clouds. What weather?', options: ['Sunny', 'Windy', 'Rainy', 'Cloudy'], answer: 2 },
                { emoji: '💨', question: 'The flag is flying because of strong ___.',  options: ['Rain', 'Wind', 'Snow', 'Sun'], answer: 1 },
                { emoji: '⛈️', question: 'Thunder and lightning. What weather?',       options: ['Sunny', 'Rainy', 'Stormy', 'Cloudy'], answer: 2 },
                { emoji: '☁️', question: 'The sky is gray and you can\'t see the sun.', options: ['Sunny', 'Rainy', 'Foggy', 'Cloudy'], answer: 3 },
                { emoji: '🌡️', question: 'It\'s very warm. You should drink water. This is ___ weather.', options: ['Cold', 'Hot', 'Rainy', 'Windy'], answer: 1 },
            ]
        },
        {
            id: 'sc-weather-clothes',
            subject: 'selfcare',
            icon: '👕',
            title: 'Weather Clothes',
            desc: 'What to wear in different weather?',
            type: 'choose',
            instruction: 'Pick the right item for this weather!',
            rounds: [
                { emoji: '🌧️', question: 'It\'s raining. What should you bring?', options: ['Sunglasses', 'Umbrella', 'Swimsuit', 'Fan'], answer: 1 },
                { emoji: '☀️', question: 'It\'s very sunny. What should you wear?', options: ['Jacket', 'Raincoat', 'Hat & sunglasses', 'Boots'], answer: 2 },
                { emoji: '❄️', question: 'It\'s very cold. What should you wear?', options: ['Shorts', 'Jacket & scarf', 'Swimsuit', 'Sando'], answer: 1 },
                { emoji: '💨', question: 'It\'s very windy. What should you do?', options: ['Go swimming', 'Stay inside or wear a windbreaker', 'Wear a hat loosely', 'Go running'], answer: 1 },
                { emoji: '🏖️', question: 'It\'s a hot day at the beach. What to wear?', options: ['Jacket', 'Boots', 'Light clothes & sandals', 'Scarf'], answer: 2 },
                { emoji: '🌧️', question: 'Walking in the rain, your feet need ___.',  options: ['Sandals', 'Rain boots', 'Slippers', 'Bare feet'], answer: 1 },
            ]
        },
        {
            id: 'sc-animals',
            subject: 'selfcare',
            icon: '🐾',
            title: 'Pet, Farm or Zoo?',
            desc: 'Sort animals into their correct group!',
            type: 'classify',
            instruction: 'Where does this animal belong?',
            config: {
                categories: [
                    { id: 'pet',  label: '🏠 Pet',  color: '#8b5cf6' },
                    { id: 'farm', label: '🌾 Farm', color: '#f59e0b' },
                    { id: 'zoo',  label: '🦁 Zoo',  color: '#ef4444' }
                ]
            },
            rounds: [
                { emoji: '🐕', label: 'Dog',      answer: 'pet' },
                { emoji: '🐄', label: 'Cow',      answer: 'farm' },
                { emoji: '🦁', label: 'Lion',     answer: 'zoo' },
                { emoji: '🐱', label: 'Cat',      answer: 'pet' },
                { emoji: '🐔', label: 'Chicken',  answer: 'farm' },
                { emoji: '🐘', label: 'Elephant', answer: 'zoo' },
                { emoji: '🐹', label: 'Hamster',  answer: 'pet' },
                { emoji: '🐖', label: 'Pig',      answer: 'farm' },
                { emoji: '🐒', label: 'Monkey',   answer: 'zoo' },
                { emoji: '🐠', label: 'Fish',     answer: 'pet' },
                { emoji: '🐴', label: 'Horse',    answer: 'farm' },
                { emoji: '🦒', label: 'Giraffe',  answer: 'zoo' },
            ]
        },
        {
            id: 'sc-food',
            subject: 'selfcare',
            icon: '🍎',
            title: 'Healthy or Unhealthy?',
            desc: 'Sort food into Healthy or Unhealthy!',
            type: 'classify',
            instruction: 'Is this food HEALTHY or UNHEALTHY?',
            config: {
                categories: [
                    { id: 'healthy',   label: '✅ Healthy',   color: '#10b981' },
                    { id: 'unhealthy', label: '❌ Unhealthy', color: '#ef4444' }
                ]
            },
            rounds: [
                { emoji: '🍎', label: 'Apple',        answer: 'healthy' },
                { emoji: '🍬', label: 'Candy',        answer: 'unhealthy' },
                { emoji: '🥕', label: 'Carrot',       answer: 'healthy' },
                { emoji: '🍟', label: 'French Fries', answer: 'unhealthy' },
                { emoji: '🥛', label: 'Milk',         answer: 'healthy' },
                { emoji: '🥤', label: 'Soda',         answer: 'unhealthy' },
                { emoji: '🍚', label: 'Rice',         answer: 'healthy' },
                { emoji: '🍰', label: 'Cake',         answer: 'unhealthy' },
                { emoji: '🥚', label: 'Egg',          answer: 'healthy' },
                { emoji: '🍩', label: 'Donut',        answer: 'unhealthy' },
            ]
        },
        {
            id: 'sc-rawfood',
            subject: 'selfcare',
            icon: '🍳',
            title: 'Ready-to-Eat or Raw?',
            desc: 'Can you eat it right away, or does it need cooking?',
            type: 'classify',
            instruction: 'Is this READY-TO-EAT or RAW (needs cooking)?',
            config: {
                categories: [
                    { id: 'ready', label: '🍽️ Ready-to-Eat', color: '#10b981' },
                    { id: 'raw',   label: '🔥 Raw (Cook it!)', color: '#f59e0b' }
                ]
            },
            rounds: [
                { emoji: '🍌', label: 'Banana',       answer: 'ready' },
                { emoji: '🥩', label: 'Raw Meat',     answer: 'raw' },
                { emoji: '🍞', label: 'Bread',        answer: 'ready' },
                { emoji: '🥚', label: 'Raw Egg',      answer: 'raw' },
                { emoji: '🍎', label: 'Apple',        answer: 'ready' },
                { emoji: '🐟', label: 'Raw Fish',     answer: 'raw' },
                { emoji: '🧃', label: 'Juice',        answer: 'ready' },
                { emoji: '🍗', label: 'Raw Chicken',  answer: 'raw' },
                { emoji: '🍪', label: 'Cookie',       answer: 'ready' },
                { emoji: '🥔', label: 'Raw Potato',   answer: 'raw' },
            ]
        },
        {
            id: 'sc-eating-habits',
            subject: 'selfcare',
            icon: '🥗',
            title: 'Good Eating Habits',
            desc: 'Learn what makes a good eating habit!',
            type: 'choose',
            instruction: 'Which is a GOOD eating habit?',
            rounds: [
                { emoji: '🍽️', question: 'Before eating, you should always ___.', options: ['Run around', 'Wash your hands', 'Watch TV', 'Touch the food'], answer: 1 },
                { emoji: '🥦', question: 'You should eat ___ every day to be healthy.', options: ['Candy', 'Chips', 'Vegetables & fruits', 'Soda'], answer: 2 },
                { emoji: '💧', question: 'What is the best drink for your body?', options: ['Soda', 'Water', 'Coffee', 'Energy drink'], answer: 1 },
                { emoji: '⏰', question: 'How many meals should you eat every day?', options: ['1', '2', '3', '5'], answer: 2 },
                { emoji: '🍴', question: 'When should you chew your food?', options: ['Fast', 'Slowly and carefully', 'While running', 'While lying down'], answer: 1 },
                { emoji: '🧼', question: 'To keep food clean, you should ___.', options: ['Leave it on the floor', 'Cover and store properly', 'Touch it a lot', 'Leave it outside'], answer: 1 },
            ]
        },

        /* ────────────── TRUE / FALSE ────────────── */
        {
            id: 'math-truefalse',
            subject: 'math',
            icon: '🔍',
            title: 'Math True or False?',
            desc: 'Is the math statement true or false? Decide fast!',
            type: 'truefalse',
            instruction: 'Is this statement TRUE or FALSE?',
            rounds: [
                { emoji: '➕', statement: '5 + 3 = 8',             answer: true,  explanation: '5 + 3 does equal 8!' },
                { emoji: '➖', statement: '10 − 4 = 5',            answer: false, explanation: '10 − 4 = 6, not 5.' },
                { emoji: '🔢', statement: '7 is bigger than 12',    answer: false, explanation: '7 < 12, so 7 is SMALLER.' },
                { emoji: '➕', statement: '4 + 4 = 8',             answer: true,  explanation: '4 + 4 does equal 8!' },
                { emoji: '↙️', statement: '20 is smaller than 15',  answer: false, explanation: '20 > 15. Twenty is bigger!' },
                { emoji: '✖️', statement: '3 × 3 = 9',             answer: true,  explanation: '3 times 3 equals 9!' },
                { emoji: '🔢', statement: '100 is greater than 99', answer: true,  explanation: '100 comes after 99!' },
                { emoji: '➖', statement: '15 − 5 = 8',            answer: false, explanation: '15 − 5 = 10, not 8.' },
            ]
        },
        {
            id: 'eng-truefalse',
            subject: 'english',
            icon: '📖',
            title: 'English True or False?',
            desc: 'Are these reading and spelling facts true or false?',
            type: 'truefalse',
            instruction: 'Is this statement TRUE or FALSE?',
            rounds: [
                { emoji: '🔤', statement: '"SIT" contains the short /i/ sound.',       answer: true,  explanation: 'S-I-T has the /i/ in the middle!' },
                { emoji: '🔤', statement: '"CAT" is spelled C-A-T.',                   answer: true,  explanation: 'Yes! C-A-T spells CAT.' },
                { emoji: '🐖', statement: '"PIG" starts with the letter B.',            answer: false, explanation: 'PIG starts with the letter P!' },
                { emoji: '📝', statement: '"IN" means the same as inside.',             answer: true,  explanation: '"The toy is IN the box." — inside!' },
                { emoji: '🔤', statement: 'The word "BIG" rhymes with "DIG".',         answer: true,  explanation: 'BIG and DIG both end in -IG!' },
                { emoji: '🔤', statement: '"LIP" has 4 letters.',                      answer: false, explanation: 'LIP has only 3 letters: L-I-P.' },
                { emoji: '📖', statement: 'A sentence starts with a capital letter.',  answer: true,  explanation: 'Always start sentences with a capital!' },
                { emoji: '🔤', statement: '"HIT" and "SIT" rhyme.',                    answer: true,  explanation: 'Both end in -IT — they rhyme!' },
            ]
        },
        {
            id: 'sc-truefalse',
            subject: 'selfcare',
            icon: '🌍',
            title: 'Science True or False?',
            desc: 'True or false? Test your science and health knowledge!',
            type: 'truefalse',
            instruction: 'Is this statement TRUE or FALSE?',
            rounds: [
                { emoji: '🐶', statement: 'A dog is a living thing.',                       answer: true,  explanation: 'Dogs eat, breathe, and grow — living!' },
                { emoji: '🪨', statement: 'A rock can grow and breathe.',                   answer: false, explanation: 'Rocks are non-living — they cannot grow or breathe.' },
                { emoji: '☀️', statement: 'Sunny weather means the sun is shining.',        answer: true,  explanation: 'Sunny = the sun is out and bright!' },
                { emoji: '🍬', statement: 'Eating candy every day is healthy.',             answer: false, explanation: 'Too much candy is unhealthy for your body!' },
                { emoji: '🧼', statement: 'You should wash your hands before eating.',      answer: true,  explanation: 'Always wash hands before meals to stay safe!' },
                { emoji: '💧', statement: 'Soda is healthier than water.',                  answer: false, explanation: 'Water is always the healthiest drink!' },
                { emoji: '🐄', statement: 'A cow is a farm animal.',                        answer: true,  explanation: 'Cows live on farms!' },
                { emoji: '🌡️', statement: 'In hot weather, you should drink less water.',  answer: false, explanation: 'In hot weather, drink MORE water to stay hydrated!' },
            ]
        },

        /* ────────────── MATCH-PAIRS (memory card flip) ────────────── */
        {
            id: 'math-pairs',
            subject: 'math',
            icon: '🃏',
            title: 'Math Match-Up',
            desc: 'Flip cards to match math problems with their answers!',
            type: 'match-pairs',
            instruction: 'Flip two cards — match the problem to its answer!',
            rounds: [{ pairs: [
                ['2 + 3', '5'],
                ['4 + 2', '6'],
                ['10 − 3', '7'],
                ['6 + 2', '8'],
                ['2 + 2', '4'],
                ['3 + 6', '9'],
            ]}]
        },
        {
            id: 'eng-pairs',
            subject: 'english',
            icon: '🃏',
            title: 'Word-Picture Match',
            desc: 'Flip cards to match each picture with its correct word!',
            type: 'match-pairs',
            instruction: 'Flip two cards — match the picture to its word!',
            rounds: [{ pairs: [
                ['🐱', 'CAT'],
                ['🐕', 'DOG'],
                ['🐟', 'FISH'],
                ['🐦', 'BIRD'],
                ['🐸', 'FROG'],
                ['🐢', 'TURTLE'],
            ]}]
        },
        {
            id: 'sc-pairs',
            subject: 'selfcare',
            icon: '🃏',
            title: 'Weather Match-Up',
            desc: 'Match the weather emoji to its name!',
            type: 'match-pairs',
            instruction: 'Flip two cards — match the emoji to its weather word!',
            rounds: [{ pairs: [
                ['☀️', 'Sunny'],
                ['🌧️', 'Rainy'],
                ['💨', 'Windy'],
                ['⛈️', 'Stormy'],
                ['☁️', 'Cloudy'],
                ['❄️', 'Cold'],
            ]}]
        },
    ];


    /* ══════════════════════════════════════════════════════════
       DEFAULT GAMES — the 9 basic SPED-friendly activities
       always visible to all students (3 per subject)
       ══════════════════════════════════════════════════════════ */
    const DEFAULT_GAME_IDS = [
        'math-sort-asc', 'math-compare', 'math-ordinal', 'math-truefalse', 'math-pairs',    // Math
        'eng-build-cvc', 'eng-read-cvc', 'eng-sentences', 'eng-truefalse', 'eng-pairs',    // English
        'sc-living', 'sc-food', 'sc-eating-habits', 'sc-truefalse', 'sc-pairs',            // Self Care
    ];

    /* ══════════════════════════════════════════════════════════
       STATE
       ══════════════════════════════════════════════════════════ */
    let currentSubject  = 'math';
    let currentActivity = null;
    let sessionRounds   = [];
    let currentRound    = 0;
    let score           = 0;
    let streak          = 0;
    let bestStreak      = 0;
    let correctCount    = 0;
    let totalRounds     = 0;
    let answered        = false;  // prevents double-answering

    // Build-word / Sort-order state
    let buildAnswer     = [];
    let sortPicked      = [];

    /* Timer + Attempt system state */
    let roundTimer      = null;
    let roundAdvanceTimer = null;
    let roundAttempts   = null;
    const MAX_GAME_ATTEMPTS = 3;
    let GAME_TIMER_SECONDS = 30;  // default; overridden by teacher settings
    let showGameScore = true;     // default; overridden by teacher settings

    /* Games enabled for this student — starts with defaults, updated by API */
    let enabledGameIds = [...DEFAULT_GAME_IDS];

    /* Power-up state — reset each game (one use each) */
    let powerups = { hint: true, extratime: true, skip: true };

    /* ── Attempt tracking state ── */
    let _actAttemptId = 0;
    let _actGameStartTime = 0;
    let _actHistoryStats = null; // { total_plays, completed_plays, best_score, last_played }
    const _lastRoundOrderByActivity = new Map();

    const $ = id => document.getElementById(id);

    /* ══════════════════════════════════════════════════════════
       INIT
       ══════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', async () => {
        // Fetch teacher timer setting + enabled games + teacher activities in parallel
        fetchTimerSetting();
        await fetchEnabledGames();
        await fetchTeacherActivities(); // Fetch and merge teacher-created activities
        fetchActivityHistory(); // non-blocking; updates hub when ready

        // Subject tabs
        document.querySelectorAll('.subject-tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.subject-tab-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                currentSubject = btn.dataset.subject;
                renderHub();
            });
        });

        // Buttons
        $('btnStart').addEventListener('click', async () => {
            await actStartAttempt();
            startGame();
        });
        $('btnBackHub').addEventListener('click', () => showScreen('hub'));
        $('btnRetry').addEventListener('click', async () => {
            await actStartAttempt();
            startGame();
        });
        $('btnBackResults').addEventListener('click', () => showScreen('hub'));

        // Abandon attempt on page leave
        window.addEventListener('beforeunload', () => actAbandonAttempt());

        // Deep link support: ?id=math-sort-asc (only if game is enabled)
        const params = new URLSearchParams(window.location.search);
        const deepId = params.get('id');
        if (deepId) {
            const act = BANK.find(a => a.id === deepId && enabledGameIds.includes(a.id));
            if (act) { showIntro(act); return; }
        }

        renderHub();
        loadNavStats();
    });

    /* ══════════════════════════════════════════════════════════
       SCREEN MANAGEMENT
       ══════════════════════════════════════════════════════════ */
    function showScreen(name) {
        ['hubScreen', 'introScreen', 'gameScreen', 'resultsScreen'].forEach(id => {
            $(id).classList.add('hidden');
        });
        $(name + 'Screen').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /* ══════════════════════════════════════════════════════════
       HUB — activity list
       ══════════════════════════════════════════════════════════ */
    function renderHub() {
        showScreen('hub');
        const list = $('activityList');
        const filtered = BANK
            .filter(a => a.subject === currentSubject && enabledGameIds.includes(a.id))
            .sort((a, b) => {
                const aTeacher = isTeacherActivity(a) ? 1 : 0;
                const bTeacher = isTeacherActivity(b) ? 1 : 0;
                return bTeacher - aTeacher;
            });

        if (filtered.length === 0) {
            list.innerHTML = '<p style="text-align:center;color:#9ca3af;padding:30px">No activities yet for this subject.</p>';
            return;
        }

        list.innerHTML = filtered.map(a => {
            const typeLabel = { 'sort-order': 'Sort', 'classify': 'Classify', 'compare': 'Compare', 'choose': 'Quiz', 'build-word': 'Build', 'match-pairs': 'Memory', 'truefalse': 'T/F' }[a.type] || 'Play';
            return `
            <div class="activity-card" data-id="${a.id}">
                <div class="ac-icon-wrap ${a.subject}"><span>${a.icon}</span></div>
                <div class="ac-info">
                    <h3>${esc(a.title)}</h3>
                    <p>${esc(a.desc)}</p>
                </div>
                <span class="ac-badge">${a.rounds.length} rounds</span>
            </div>`;
        }).join('');

        list.querySelectorAll('.activity-card').forEach(card => {
            card.addEventListener('click', () => {
                const act = BANK.find(a => a.id === card.dataset.id);
                if (act) showIntro(act);
            });
        });

        // Show game history section if stats are available
        const histSection = $('actHistorySection');
        if (histSection && _actHistoryStats && _actHistoryStats.total_plays > 0) {
            histSection.innerHTML = renderActivityHistoryHTML(_actHistoryStats);
            histSection.classList.remove('hidden');
        } else if (histSection) {
            histSection.classList.add('hidden');
        }
    }

    /* ══════════════════════════════════════════════════════════
       INTRO SCREEN
       ══════════════════════════════════════════════════════════ */
    function showIntro(activity) {
        currentActivity = activity;
        $('introIcon').textContent  = activity.icon;
        $('introTitle').textContent = activity.title;
        $('introDesc').textContent  = activity.desc;
        $('introRounds').textContent = '📝 ' + activity.rounds.length + ' rounds';

        const typeLabel = { 'sort-order': '↕️ Sorting', 'classify': '📂 Classify', 'compare': '⚖️ Compare', 'choose': '✅ Quiz', 'build-word': '🔤 Build Word', 'match-pairs': '🃏 Memory', 'truefalse': '✅❌ True/False' }[activity.type] || '🎮 Play';
        $('introType').textContent = typeLabel;

        // Show/hide attempt history below the intro card
        const ih = $('actIntroHistory');
        if (ih) {
            if (_actHistoryStats && _actHistoryStats.total_plays > 0) {
                ih.innerHTML = renderActivityHistoryHTML(_actHistoryStats);
                ih.classList.remove('hidden');
            } else {
                ih.classList.add('hidden');
            }
        }

        showScreen('intro');
    }

    /* ══════════════════════════════════════════════════════════
       GAME ENGINE
       ══════════════════════════════════════════════════════════ */
    function startGame() {
        if (!currentActivity) return;
        if (roundAdvanceTimer) {
            clearTimeout(roundAdvanceTimer);
            roundAdvanceTimer = null;
        }
        sessionRounds = buildSessionRounds(currentActivity);
        currentRound = 0;
        score        = 0;
        streak       = 0;
        bestStreak   = 0;
        correctCount = 0;
        totalRounds  = sessionRounds.length;
        resetPowerups();
        clearComboBanner();
        // Remove stale powerups bar so it re-renders fresh
        const oldPU = $('gamePowerupsArea');
        if (oldPU) oldPU.remove();
        showScreen('game');
        nextRound();

        // Guide greets at game start
        if (window.GuideCharacter) {
            GuideCharacter.show('neutral');
        }
    }

    async function nextRound() {
        if (currentRound >= totalRounds) { endGame(); return; }

        answered = false;
        if (roundAdvanceTimer) {
            clearTimeout(roundAdvanceTimer);
            roundAdvanceTimer = null;
        }

        // Stop previous timer
        if (roundTimer) roundTimer.stop();

        // Reset attempt tracker
        if (window.GameHelpers) {
            roundAttempts = GameHelpers.createAttemptTracker(MAX_GAME_ATTEMPTS);
        }

        // Boss announcement on the FINAL round (not for match-pairs)
        const isFinal = currentRound === totalRounds - 1 && totalRounds > 2;
        if (isFinal && currentActivity.type !== 'match-pairs') {
            await showBossAnnouncement();
        }

        // Update HUD
        $('gRound').textContent  = (currentRound + 1) + '/' + totalRounds;
        $('gScore').textContent  = score;
        renderHudStars();
        $('gInstruction').textContent = 'QUESTION: ' + currentActivity.instruction;

        // Clear
        $('gameArea').innerHTML    = '';
        $('gameActions').innerHTML = '';

        // Update attempts display
        updateAttemptsDisplay();

        // Show combo banner (if streak carried over)
        updateComboBanner();

        // Render power-ups bar (skip for match-pairs — handled inside renderMatchPairs)
        if (currentActivity.type !== 'match-pairs' && currentActivity.type !== 'truefalse') {
            renderPowerupsBar();
        }

        // Render by type
        const round = sessionRounds[currentRound];
        const type  = currentActivity.type;

        if (type === 'sort-order')   renderSortOrder(round);
        else if (type === 'classify')    renderClassify(round);
        else if (type === 'compare')     renderCompare(round);
        else if (type === 'choose')      renderChoose(round);
        else if (type === 'build-word')  renderBuildWord(round);
        else if (type === 'truefalse')   renderTrueFalse(round);
        else if (type === 'match-pairs') renderMatchPairs(round);

        // Start timer (skipped for match-pairs inside startRoundTimer)
        startRoundTimer();

        // Guide encouragement (every 3rd round)
        if (window.GuideCharacter && currentRound > 0 && currentRound % 3 === 0) {
            GuideCharacter.say('encouraging');
        }
    }

    function endGame() {
        if (roundTimer) roundTimer.stop();
        const accuracy = totalRounds > 0 ? Math.round((correctCount / totalRounds) * 100) : 0;
        const xpEarned = Math.max(5, Math.round(score / 8));

        const emoji = accuracy >= 80 ? '🏆' : accuracy >= 50 ? '⭐' : '💪';
        const title = accuracy >= 80 ? 'Amazing Job!' : accuracy >= 50 ? 'Good Work!' : 'Keep Practicing!';

        $('rEmoji').textContent  = emoji;
        $('rTitle').textContent  = title;
        if (showGameScore) {
            $('rScore').textContent  = score;
            $('rDetail').textContent = correctCount + '/' + totalRounds + ' correct · Best streak: ' + bestStreak;
        } else {
            $('rScore').textContent  = '⭐';
            $('rDetail').textContent = 'Activity complete!';
        }

        showScreen('results');
        window.dispatchEvent(new CustomEvent('petReact', { detail: { type: 'complete' } }));
        animateXPCountup(xpEarned);   // counts up from 0 → xpEarned
        awardXP(xpEarned);
        actCompleteAttempt(xpEarned);
        submitQuestGrade();

        // Gamified popup — celebrate completion
        if (window.showGamePopup) {
            var popMsg = accuracy >= 80
                ? 'Incredible work! You crushed that activity!'
                : accuracy >= 50
                    ? 'You did a great job finishing that activity. Keep it up!'
                    : 'You finished! Every attempt makes you stronger. Try again!';
            showGamePopup({
                type:      'success',
                title:     'Activity Complete! \uD83C\uDF1F',
                icon:      accuracy >= 80 ? '\uD83C\uDFC6' : '\uD83C\uDF1F',
                message:   popMsg,
                confetti:  accuracy >= 50,
                autoClose: 4000,
            });
        }

        // Guide celebrates or comforts
        if (window.GuideCharacter) {
            if (accuracy >= 80) GuideCharacter.say('celebrating');
            else if (accuracy >= 50) GuideCharacter.say('encouraging');
            else GuideCharacter.say('comforting');
        }
        if (accuracy >= 80 && window.GameHelpers) GameHelpers.sparkleEffect();
    }

    /* ── After answering: freeze + auto-advance ── */
    function showNextButton(delay = 950) {
        if (roundTimer) roundTimer.stop();
        $('gameActions').innerHTML = '';
        if (roundAdvanceTimer) clearTimeout(roundAdvanceTimer);
        roundAdvanceTimer = setTimeout(() => {
            if (!$('gameScreen') || $('gameScreen').classList.contains('hidden')) return;
            currentRound++;
            nextRound();
        }, delay);
    }

    function renderHudStars() {
        if (!roundAttempts) return;
        const state = roundAttempts.getState();
        let stars = '';
        for (let i = 0; i < state.maxAttempts; i++) {
            if (i < state.attemptsLeft) {
                stars += '❤️';
            } else {
                stars += '<span style="opacity:0.2;filter:grayscale(1)">❤️</span>';
            }
        }
        $('gStreak').innerHTML = stars;
    }

    function recordCorrect() {
        correctCount++;
        streak++;
        if (streak > bestStreak) bestStreak = streak;
        window.dispatchEvent(new CustomEvent('petReact', { detail: { type: streak >= 3 ? 'streak' : 'correct' } }));
        const bonus = Math.min(streak - 1, 5) * 10;
        const pts   = 100 + bonus;
        score += pts;
        $('gScore').textContent = score;

        // Gamified feedback
        const comboText = streak >= 2 ? (streak >= 5 ? '🔥🔥 ON FIRE!' : '🔥 x' + streak + ' COMBO!') : null;
        showFloatingScore(pts, comboText);
        updateComboBanner();

        // Stars: 3 on first try, 2 on second, 1 on third
        const starsEarned = !roundAttempts ? 3 :
            (roundAttempts.getState().attemptsUsed === 0 ? 3 :
             roundAttempts.getState().attemptsUsed === 1 ? 2 : 1);
        showStarFlash(starsEarned);
    }

    function recordWrong() {
        streak = 0;
        window.dispatchEvent(new CustomEvent('petReact', { detail: { type: 'wrong' } }));
        clearComboBanner();
        if (roundAttempts) {
            roundAttempts.use();
            renderHudStars();
            updateAttemptsDisplay();
        }
    }

    /* ── Timer helpers ── */
    function startRoundTimer() {
        const timerWrap = $('gameTimerArea');
        if (!timerWrap || !window.GameHelpers) return;
        // Memory card game has no timer — it's free-form
        if (currentActivity && currentActivity.type === 'match-pairs') { timerWrap.innerHTML = ''; return; }
        // True/False uses a shorter 15s timer for fast-paced feel
        if (currentActivity && currentActivity.type === 'truefalse') { GAME_TIMER_SECONDS = Math.min(GAME_TIMER_SECONDS, 15); }
        if (GAME_TIMER_SECONDS <= 0) { timerWrap.innerHTML = ''; return; }  // timer disabled

        roundTimer = GameHelpers.createTimer({
            duration: GAME_TIMER_SECONDS,
            onTick(state) {
                const track = document.getElementById('roundTimerTrack');
                const text = document.getElementById('roundTimerText');
                if (track) {
                    track.style.width = state.percentLeft + '%';
                    track.className = 'timer-fill';
                    if (state.percentLeft <= 25) track.classList.add('timer-red');
                    else if (state.percentLeft <= 50) track.classList.add('timer-yellow');
                    else track.classList.add('timer-green');
                }
                if (text) text.textContent = state.timeLeft + 's';
                if (state.percentLeft === 25 && window.GuideCharacter) {
                    GuideCharacter.say('timeWarning');
                }
            },
            onTimeout() { handleRoundTimeout(); },
        });

        timerWrap.innerHTML = `
            <div class="quiz-timer">
                <div class="timer-track">
                    <div class="timer-fill timer-green" id="roundTimerTrack" style="width:100%"></div>
                </div>
                <span class="timer-text" id="roundTimerText">${GAME_TIMER_SECONDS}s</span>
            </div>`;
        roundTimer.start();
    }

    function updateAttemptsDisplay() {
        const wrap = $('gameAttemptsArea');
        if (!wrap || !roundAttempts || !window.GameHelpers) return;
        wrap.innerHTML = GameHelpers.renderAttemptStars(roundAttempts.getState());
    }

    function handleRoundTimeout() {
        if (answered) return;
        answered = true;
        recordWrong();
        if (window.GuideCharacter) GuideCharacter.say('comforting');
        showFeedback("Time's up! Let's see the answer! 💛", 'wrong');
        revealCurrentRoundAnswer();
        showNextButton();
    }

    function revealCurrentRoundAnswer() {
        const round = sessionRounds[currentRound];
        const type  = currentActivity.type;
        const area  = $('gameArea');

        if (type === 'choose') {
            area.querySelectorAll('.choose-opt').forEach(o => o.classList.add('disabled'));
            const correct = area.querySelector(`[data-idx="${round.answer}"]`);
            if (correct) { correct.classList.remove('disabled'); correct.classList.add('correct'); }
        } else if (type === 'classify') {
            const correct = area.querySelector(`[data-cat="${round.answer}"]`);
            if (correct) correct.classList.add('correct');
        } else if (type === 'compare') {
            const correct = area.querySelector(`[data-sym="${round.answer}"]`);
            if (correct) correct.classList.add('correct');
        } else if (type === 'sort-order') {
            const dir = currentActivity.config?.direction || 'asc';
            const correct = [...round.items].sort((a, b) => dir === 'asc' ? a - b : b - a);
            showFeedback('The correct order is: ' + correct.join(', '), 'wrong');
        } else if (type === 'build-word') {
            showFeedback('The word is: ' + round.word, 'wrong');
        }
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: SORT ORDER
       Tap items one-by-one in correct order
       ══════════════════════════════════════════════════════════ */
    function renderSortOrder(round) {
        const area    = $('gameArea');
        const items   = shuffle([...round.items]);
        const dir     = currentActivity.config?.direction || 'asc';
        const correct = [...round.items].sort((a, b) => dir === 'asc' ? a - b : b - a);

        sortPicked = [];

        let html = '<div class="sort-answer" id="sortAnswer"><span class="sort-label">Your order:</span></div>';
        html += '<div class="sort-source" id="sortSource">';
        items.forEach((n, i) => {
            html += `<button class="sort-tile" data-val="${n}" data-idx="${i}">${n}</button>`;
        });
        html += '</div>';
        area.innerHTML = html;

        // Actions: Check + Clear
        $('gameActions').innerHTML = '<button class="btn-clear" id="actClear">🗑️ Clear</button><button class="btn-check" id="actCheck" disabled>✓ Check</button>';

        // Events
        const source  = $('sortSource');
        const answer  = $('sortAnswer');
        const btnCheck = $('actCheck');
        const btnClear = $('actClear');

        source.querySelectorAll('.sort-tile').forEach(tile => {
            tile.addEventListener('click', () => {
                if (answered || tile.classList.contains('placed')) return;
                tile.classList.add('placed');
                sortPicked.push(parseInt(tile.dataset.val));

                const clone = document.createElement('button');
                clone.className = 'sort-tile in-answer';
                clone.textContent = tile.dataset.val;
                clone.dataset.srcIdx = tile.dataset.idx;
                clone.addEventListener('click', () => {
                    if (answered) return;
                    // Remove the tile at its current position in the answer area
                    const idx = [...answer.querySelectorAll('.sort-tile.in-answer')].indexOf(clone);
                    if (idx !== -1) sortPicked.splice(idx, 1);
                    clone.remove();
                    tile.classList.remove('placed');
                    btnCheck.disabled = sortPicked.length < items.length;
                });
                answer.appendChild(clone);

                btnCheck.disabled = sortPicked.length < items.length;
            });
        });

        btnClear.addEventListener('click', () => {
            if (answered) return;
            sortPicked = [];
            answer.querySelectorAll('.sort-tile.in-answer').forEach(c => c.remove());
            source.querySelectorAll('.sort-tile').forEach(t => t.classList.remove('placed'));
            btnCheck.disabled = true;
        });

        btnCheck.addEventListener('click', () => {
            if (answered) return;

            const answerTiles = answer.querySelectorAll('.sort-tile.in-answer');
            let allRight = true;
            answerTiles.forEach((t, i) => {
                if (parseInt(t.textContent) === correct[i]) {
                    t.classList.add('correct');
                } else {
                    t.classList.add('wrong');
                    allRight = false;
                }
            });

            if (allRight) {
                answered = true;
                recordCorrect();
                showFeedback('Correct! 🎉', 'correct');
                if (window.GuideCharacter) GuideCharacter.say('celebrating');
                if (window.GameHelpers) GameHelpers.sparkleEffect();
                showNextButton();
            } else if (roundAttempts) {
                const result = roundAttempts.use();
                updateAttemptsDisplay();

                if (result.isLastAttempt) {
                    answered = true;
                    recordWrong();
                    showFeedback('The correct order is: ' + correct.join(', '), 'wrong');
                    if (window.GuideCharacter) GuideCharacter.say('lastAttempt');
                    showNextButton();
                } else {
                    // Clear for retry
                    setTimeout(() => {
                        sortPicked = [];
                        answer.querySelectorAll('.sort-tile.in-answer').forEach(c => c.remove());
                        source.querySelectorAll('.sort-tile').forEach(t => t.classList.remove('placed'));
                        btnCheck.disabled = true;
                    }, 1200);
                    showFeedback(result.attemptsUsed === 1 ? "Not quite! Try a different order! 🔍" : "One more try! Look carefully! 💡", 'hint');
                    if (window.GuideCharacter) GuideCharacter.say('hinting');
                }
            } else {
                answered = true;
                recordWrong();
                showFeedback('Not quite! The order is: ' + correct.join(', '), 'wrong');
                showNextButton();
            }
        });
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: CLASSIFY
       Show one item, pick its category
       ══════════════════════════════════════════════════════════ */
    function renderClassify(round) {
        const area   = $('gameArea');
        const cats   = currentActivity.config.categories;

        let html = '<div class="classify-categories">';
        cats.forEach(c => {
            html += `<button class="classify-bucket" data-cat="${c.id}" style="background:${c.color}">${c.label}</button>`;
        });
        html += '</div>';
        html += `<div class="classify-item">
            <span class="classify-item-emoji">${round.emoji}</span>
            <span class="classify-item-label">${esc(round.label)}</span>
        </div>`;
        area.innerHTML = html;

        // Click category
        area.querySelectorAll('.classify-bucket').forEach(btn => {
            btn.addEventListener('click', () => {
                if (answered) return;

                const picked = btn.dataset.cat;
                if (picked === round.answer) {
                    answered = true;
                    btn.classList.add('correct');
                    recordCorrect();
                    showFeedback('Correct! 🎉', 'correct');
                    if (window.GuideCharacter) GuideCharacter.say('celebrating');
                    if (window.GameHelpers) GameHelpers.sparkleEffect();
                    setTimeout(() => { if (answered) { currentRound++; nextRound(); } }, 950);
                } else {
                    btn.classList.add('wrong');
                    btn.style.pointerEvents = 'none';

                    if (roundAttempts) {
                        const result = roundAttempts.use();
                        updateAttemptsDisplay();

                        if (result.isLastAttempt) {
                            answered = true;
                            area.querySelector(`[data-cat="${round.answer}"]`).classList.add('correct');
                            recordWrong();
                            showFeedback(round.emoji + ' ' + round.label + ' belongs to ' + cats.find(c => c.id === round.answer).label, 'wrong');
                            if (window.GuideCharacter) GuideCharacter.say('lastAttempt');
                            showNextButton();
                        } else {
                            showFeedback(result.attemptsUsed === 1 ? "Not that one! Try another! 🔍" : "One more try! Think carefully! 💡", 'hint');
                            if (window.GuideCharacter) GuideCharacter.say('hinting');
                        }
                    } else {
                        answered = true;
                        area.querySelector(`[data-cat="${round.answer}"]`).classList.add('correct');
                        recordWrong();
                        showFeedback(round.emoji + ' ' + round.label + ' belongs to ' + cats.find(c => c.id === round.answer).label, 'wrong');
                        showNextButton();
                    }
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: COMPARE
       Two numbers + <, =, > buttons
       ══════════════════════════════════════════════════════════ */
    function renderCompare(round) {
        const area = $('gameArea');
        area.innerHTML = `
        <div class="compare-display">
            <span class="compare-num">${round.left}</span>
            <span class="compare-vs">?</span>
            <span class="compare-num">${round.right}</span>
        </div>
        <div class="compare-buttons">
            <button class="compare-btn" data-sym="<">&lt;</button>
            <button class="compare-btn" data-sym="=">=</button>
            <button class="compare-btn" data-sym=">">&gt;</button>
        </div>`;

        area.querySelectorAll('.compare-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (answered) return;

                const picked = btn.dataset.sym;
                if (picked === round.answer) {
                    answered = true;
                    btn.classList.add('correct');
                    recordCorrect();
                    showFeedback('Correct! ' + round.left + ' ' + round.answer + ' ' + round.right + ' ✅', 'correct');
                    if (window.GuideCharacter) GuideCharacter.say('celebrating');
                    if (window.GameHelpers) GameHelpers.sparkleEffect();
                    setTimeout(() => { if (answered) { currentRound++; nextRound(); } }, 950);
                } else {
                    btn.classList.add('wrong');
                    btn.style.pointerEvents = 'none';

                    if (roundAttempts) {
                        const result = roundAttempts.use();
                        updateAttemptsDisplay();

                        if (result.isLastAttempt) {
                            answered = true;
                            area.querySelector(`[data-sym="${round.answer}"]`).classList.add('correct');
                            recordWrong();
                            showFeedback('It\'s ' + round.left + ' ' + round.answer + ' ' + round.right, 'wrong');
                            if (window.GuideCharacter) GuideCharacter.say('lastAttempt');
                            showNextButton();
                        } else {
                            showFeedback(result.attemptsUsed === 1 ? "Not that one! Think about which is bigger! 🤔" : "Last try! Compare carefully! 💡", 'hint');
                            if (window.GuideCharacter) GuideCharacter.say('hinting');
                        }
                    } else {
                        answered = true;
                        area.querySelector(`[data-sym="${round.answer}"]`).classList.add('correct');
                        recordWrong();
                        showFeedback('It\'s ' + round.left + ' ' + round.answer + ' ' + round.right, 'wrong');
                        showNextButton();
                    }
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: CHOOSE (MCQ)
       Question + options
       ══════════════════════════════════════════════════════════ */
    function renderChoose(round) {
        const area = $('gameArea');
        const letters = ['A', 'B', 'C', 'D'];

        let html = '<div class="choose-prompt"><div class="choose-kicker">QUESTION</div>';
        if (round.emoji) html += `<span class="choose-prompt-emoji">${round.emoji}</span>`;
        html += `<span class="choose-prompt-text">${esc(round.question)}</span></div>`;
        html += '<div class="choose-options">';
        round.options.forEach((opt, i) => {
            html += `<button class="choose-opt" data-idx="${i}">
                <span class="opt-letter">${letters[i]}</span>
                <span>${esc(opt)}</span>
            </button>`;
        });
        html += '</div>';
        area.innerHTML = html;

        area.querySelectorAll('.choose-opt').forEach(btn => {
            btn.addEventListener('click', () => {
                if (answered) return;

                const idx = parseInt(btn.dataset.idx);

                if (idx === round.answer) {
                    // Correct!
                    answered = true;
                    area.querySelectorAll('.choose-opt').forEach(o => o.classList.add('disabled'));
                    btn.classList.remove('disabled');
                    btn.classList.add('correct');
                    recordCorrect();
                    showFeedback('Correct! 🎉', 'correct');
                    if (window.GuideCharacter) GuideCharacter.say('celebrating');
                    if (window.GameHelpers) GameHelpers.sparkleEffect();
                    setTimeout(() => { if (answered) { currentRound++; nextRound(); } }, 950);
                } else {
                    // Wrong — use an attempt
                    btn.classList.add('wrong', 'disabled');
                    btn.style.pointerEvents = 'none';

                    if (roundAttempts) {
                        const result = roundAttempts.use();
                        updateAttemptsDisplay();

                        if (result.isLastAttempt) {
                            answered = true;
                            area.querySelectorAll('.choose-opt').forEach(o => o.classList.add('disabled'));
                            area.querySelector(`[data-idx="${round.answer}"]`).classList.remove('disabled');
                            area.querySelector(`[data-idx="${round.answer}"]`).classList.add('correct');
                            recordWrong();
                            showFeedback('The answer is: ' + round.options[round.answer], 'wrong');
                            if (window.GuideCharacter) GuideCharacter.say('lastAttempt');
                            showNextButton();
                        } else {
                            showFeedback(result.attemptsUsed === 1 ? "Try again! Eliminate wrong answers! 🔍" : "One more try! You can do it! 💡", 'hint');
                            if (window.GuideCharacter) GuideCharacter.say('hinting');
                        }
                    } else {
                        answered = true;
                        area.querySelectorAll('.choose-opt').forEach(o => o.classList.add('disabled'));
                        area.querySelector(`[data-idx="${round.answer}"]`).classList.remove('disabled');
                        area.querySelector(`[data-idx="${round.answer}"]`).classList.add('correct');
                        recordWrong();
                        showFeedback('The answer is: ' + round.options[round.answer], 'wrong');
                        showNextButton();
                    }
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: BUILD WORD
       Tap letters to form the word
       ══════════════════════════════════════════════════════════ */
    function renderBuildWord(round) {
        const area   = $('gameArea');
        const word   = round.word;
        const allLetters = shuffle([...word.split(''), ...round.extras]);

        buildAnswer = new Array(word.length).fill(null);

        let html = '<div class="build-hint">';
        if (round.hint) {
            const parts = round.hint.split(' ');
            const emoji = /^\p{Emoji}/u.test(parts[0]) ? parts[0] : '';
            const text  = emoji ? parts.slice(1).join(' ') : round.hint;
            if (emoji) html += `<span class="build-hint-emoji">${emoji}</span>`;
            html += `<span class="build-hint-text">QUESTION: ${esc(text)}</span>`;
        }
        html += '</div>';

        // Answer slots
        html += '<div class="build-answer" id="buildAnswer">';
        for (let i = 0; i < word.length; i++) {
            html += `<div class="build-slot" data-pos="${i}"></div>`;
        }
        html += '</div>';

        // Letter tiles
        html += '<div class="build-letters" id="buildLetters">';
        allLetters.forEach((ch, i) => {
            html += `<button class="build-letter" data-idx="${i}">${ch}</button>`;
        });
        html += '</div>';
        area.innerHTML = html;

        // Actions
        $('gameActions').innerHTML = '<button class="btn-clear" id="actClear">🗑️ Clear</button><button class="btn-check" id="actCheck" disabled>✓ Check</button>';

        const answerEl  = $('buildAnswer');
        const lettersEl = $('buildLetters');
        const btnCheck  = $('actCheck');
        const btnClear  = $('actClear');

        // Tap letter → place in next empty slot
        lettersEl.querySelectorAll('.build-letter').forEach(tile => {
            tile.addEventListener('click', () => {
                if (answered || tile.classList.contains('used')) return;
                const emptyIdx = buildAnswer.indexOf(null);
                if (emptyIdx === -1) return;

                buildAnswer[emptyIdx] = { letter: tile.textContent, tileIdx: tile.dataset.idx };
                tile.classList.add('used');

                const slot = answerEl.querySelector(`[data-pos="${emptyIdx}"]`);
                slot.textContent = tile.textContent;
                slot.classList.add('filled');
                slot.dataset.tileIdx = tile.dataset.idx;

                btnCheck.disabled = buildAnswer.some(a => a === null);
            });
        });

        // Tap slot → remove letter
        answerEl.querySelectorAll('.build-slot').forEach(slot => {
            slot.addEventListener('click', () => {
                if (answered) return;
                const pos = parseInt(slot.dataset.pos);
                if (!buildAnswer[pos]) return;
                const tileIdx = buildAnswer[pos].tileIdx;
                buildAnswer[pos] = null;
                slot.textContent = '';
                slot.classList.remove('filled');
                const tile = lettersEl.querySelector(`[data-idx="${tileIdx}"]`);
                if (tile) tile.classList.remove('used');
                btnCheck.disabled = true;
            });
        });

        // Clear
        btnClear.addEventListener('click', () => {
            if (answered) return;
            buildAnswer.fill(null);
            answerEl.querySelectorAll('.build-slot').forEach(s => { s.textContent = ''; s.classList.remove('filled', 'correct', 'wrong'); });
            lettersEl.querySelectorAll('.build-letter').forEach(t => t.classList.remove('used'));
            btnCheck.disabled = true;
        });

        // Check
        btnCheck.addEventListener('click', () => {
            if (answered) return;

            const guess = buildAnswer.map(a => a.letter).join('');
            const slots = answerEl.querySelectorAll('.build-slot');

            if (guess === word) {
                answered = true;
                slots.forEach(s => s.classList.add('correct'));
                recordCorrect();
                showFeedback('Correct! 🎉 ' + word, 'correct');
                if (window.GuideCharacter) GuideCharacter.say('celebrating');
                if (window.GameHelpers) GameHelpers.sparkleEffect();
                showNextButton();
            } else {
                slots.forEach((s, i) => {
                    s.classList.add(buildAnswer[i].letter === word[i] ? 'correct' : 'wrong');
                });

                if (roundAttempts) {
                    const result = roundAttempts.use();
                    updateAttemptsDisplay();

                    if (result.isLastAttempt) {
                        answered = true;
                        recordWrong();
                        showFeedback('The word is: ' + word, 'wrong');
                        if (window.GuideCharacter) GuideCharacter.say('lastAttempt');
                        showNextButton();
                    } else {
                        // Clear for retry
                        setTimeout(() => {
                            buildAnswer.fill(null);
                            answerEl.querySelectorAll('.build-slot').forEach(s => { s.textContent = ''; s.classList.remove('filled', 'correct', 'wrong'); });
                            lettersEl.querySelectorAll('.build-letter').forEach(t => t.classList.remove('used'));
                            btnCheck.disabled = true;
                        }, 1200);
                        showFeedback(result.attemptsUsed === 1 ? "Not quite! Try different letters! 🔍" : "One more try! Remember the hint! 💡", 'hint');
                        if (window.GuideCharacter) GuideCharacter.say('hinting');
                    }
                } else {
                    answered = true;
                    recordWrong();
                    showFeedback('The word is: ' + word, 'wrong');
                    showNextButton();
                }
            }
        });

        // Keyboard support
        area._keyHandler = (e) => {
            if (answered || $('gameScreen').classList.contains('hidden')) return;
            if (e.key === 'Backspace') {
                e.preventDefault();
                for (let i = buildAnswer.length - 1; i >= 0; i--) {
                    if (buildAnswer[i]) {
                        answerEl.querySelectorAll('.build-slot')[i].click();
                        break;
                    }
                }
                return;
            }
            const key = e.key.toUpperCase();
            if (/^[A-Z]$/.test(key)) {
                const tiles = lettersEl.querySelectorAll('.build-letter:not(.used)');
                for (const t of tiles) {
                    if (t.textContent === key) { t.click(); break; }
                }
            }
        };
        document.addEventListener('keydown', area._keyHandler);
    }

    /* ══════════════════════════════════════════════════════════
       GAMIFICATION HELPERS
       ══════════════════════════════════════════════════════════ */

    /* ── Floating "+100 COMBO x3!" score popup ── */
    function showFloatingScore(pts, comboText) {
        const el = document.createElement('div');
        el.className = 'score-float';
        el.style.color = pts > 100 ? '#ea580c' : '#7c3aed';
        el.innerHTML = '+' + pts + (comboText ? '<br><span style="font-size:13px">' + comboText + '</span>' : '');
        const area = $('gameArea');
        const r = area ? area.getBoundingClientRect()
                       : { left: window.innerWidth / 2 - 30, top: window.innerHeight / 2 - 60, width: 60 };
        el.style.left = (r.left + r.width / 2 - 30) + 'px';
        el.style.top  = (r.top + 40) + 'px';
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1400);
    }

    /* ── Combo streak banner ── */
    function updateComboBanner() {
        let banner = $('comboBanner');
        if (streak < 2) { clearComboBanner(); return; }
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'comboBanner';
            const instr = $('gInstruction');
            if (instr && instr.parentNode) instr.parentNode.insertBefore(banner, instr.nextSibling);
        }
        if (streak >= 5) {
            banner.className = 'combo-banner fire';
            banner.textContent = '🔥🔥🔥 ON FIRE! x' + streak + ' COMBO!';
        } else if (streak >= 3) {
            banner.className = 'combo-banner x3';
            banner.textContent = '🔥 x' + streak + ' STREAK — HOT!';
        } else {
            banner.className = 'combo-banner x2';
            banner.textContent = '⚡ x' + streak + ' COMBO!';
        }
    }

    function clearComboBanner() {
        const b = $('comboBanner');
        if (b) b.remove();
    }

    /* ── Star flash on correct answer ── */
    function showStarFlash(stars) {
        const el = document.createElement('div');
        el.className = 'round-stars';
        el.textContent = '⭐'.repeat(Math.max(1, Math.min(stars, 3)));
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 1100);
    }

    /* ── Boss/Final round overlay ── */
    function showBossAnnouncement() {
        return new Promise(resolve => {
            const el = document.createElement('div');
            el.className = 'boss-announcement';
            el.innerHTML = `
                <div class="boss-title">⚡ FINAL CHALLENGE!</div>
                <div class="boss-sub">Last round — give it everything you've got!</div>`;
            document.body.appendChild(el);
            setTimeout(() => { el.remove(); resolve(); }, 1850);
        });
    }

    /* ── XP count-up animation on results screen ── */
    function animateXPCountup(target) {
        const el = $('rXP');
        if (!el) return;
        if (!target || target <= 0) { el.textContent = '+0 XP earned!'; return; }
        let current = 0;
        const step  = Math.max(1, Math.ceil(target / 40));
        el.classList.add('xp-counting');
        el.textContent = '+0 XP earned!';
        const iv = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = '+' + current + ' XP earned!';
            if (current >= target) {
                clearInterval(iv);
                el.classList.remove('xp-counting');
            }
        }, 35);
    }

    /* ── Extra time power-up (restarts timer with added seconds) ── */
    function addExtraTime(seconds) {
        if (!roundTimer || !window.GameHelpers) return;
        const state = roundTimer.getState();
        roundTimer.stop();
        const newDuration = (state.timeLeft || 0) + seconds;
        const timerWrap = $('gameTimerArea');
        if (!timerWrap) return;
        timerWrap.innerHTML = `
            <div class="quiz-timer">
                <div class="timer-track">
                    <div class="timer-fill timer-green" id="roundTimerTrack" style="width:100%"></div>
                </div>
                <span class="timer-text" id="roundTimerText">${newDuration}s</span>
            </div>`;
        roundTimer = GameHelpers.createTimer({
            duration: newDuration,
            onTick(st) {
                const track = document.getElementById('roundTimerTrack');
                const text  = document.getElementById('roundTimerText');
                if (track) {
                    track.style.width = st.percentLeft + '%';
                    track.className   = 'timer-fill';
                    if      (st.percentLeft <= 25) track.classList.add('timer-red');
                    else if (st.percentLeft <= 50) track.classList.add('timer-yellow');
                    else                            track.classList.add('timer-green');
                }
                if (text) text.textContent = st.timeLeft + 's';
                if (st.percentLeft === 25 && window.GuideCharacter) GuideCharacter.say('timeWarning');
            },
            onTimeout() { handleRoundTimeout(); },
        });
        roundTimer.start();
    }

    /* ── Power-ups ── */
    function resetPowerups() {
        powerups = { hint: true, extratime: true, skip: true };
    }

    function renderPowerupsBar() {
        let wrap = $('gamePowerupsArea');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'gamePowerupsArea';
            const timerArea = $('gameTimerArea');
            if (timerArea && timerArea.parentNode) {
                timerArea.parentNode.insertBefore(wrap, timerArea.nextSibling);
            }
        }
        wrap.innerHTML = `<div class="powerups-bar">
            <button class="powerup-btn${powerups.hint      ? '' : ' used'}" data-pu="hint"      title="Eliminate one wrong answer">💡 Hint</button>
            <button class="powerup-btn${powerups.extratime ? '' : ' used'}" data-pu="extratime" title="Add 10 seconds to timer">⏱️ +10s</button>
            <button class="powerup-btn${powerups.skip      ? '' : ' used'}" data-pu="skip"      title="Skip this round">⏭️ Skip</button>
        </div>`;
        wrap.querySelectorAll('.powerup-btn').forEach(btn => {
            btn.addEventListener('click', () => usePowerUp(btn.dataset.pu));
        });
    }

    function usePowerUp(type) {
        if (!powerups[type] || answered) return;
        powerups[type] = false;
        const round = sessionRounds[currentRound];
        const area  = $('gameArea');
        const t     = currentActivity.type;

        if (type === 'hint') {
            if (t === 'choose') {
                // 50/50: eliminate one random wrong option
                const opts  = [...area.querySelectorAll('.choose-opt:not(.wrong):not(.correct)')];
                const wrong = opts.filter(o => parseInt(o.dataset.idx) !== round.answer);
                if (wrong.length > 0) {
                    const pick = wrong[Math.floor(Math.random() * wrong.length)];
                    pick.classList.add('wrong', 'disabled');
                    pick.style.pointerEvents = 'none';
                }
            } else if (t === 'build-word') {
                // Highlight the correct letter for the first empty slot
                const emptyIdx = buildAnswer.indexOf(null);
                if (emptyIdx !== -1) {
                    const correctLetter = round.word[emptyIdx];
                    const lettersEl = $('buildLetters');
                    const tile = [...lettersEl.querySelectorAll('.build-letter:not(.used)')].find(tl => tl.textContent === correctLetter);
                    if (tile) { tile.style.outline = '3px solid #7c3aed'; tile.style.background = '#ede9fe'; }
                }
            } else if (t === 'compare') {
                const correct = area.querySelector(`[data-sym="${round.answer}"]`);
                if (correct) { correct.style.outline = '3px solid #7c3aed'; setTimeout(() => correct.style.outline = '', 900); }
            } else if (t === 'classify') {
                const correct = area.querySelector(`[data-cat="${round.answer}"]`);
                if (correct) { correct.style.outline = '3px solid #7c3aed'; setTimeout(() => correct.style.outline = '', 900); }
            } else if (t === 'sort-order') {
                // Highlight the next correct tile the player should pick
                const dir = currentActivity.config?.direction || 'asc';
                const correctOrder = [...round.items].sort((a, b) => dir === 'asc' ? a - b : b - a);
                const nextVal = correctOrder[sortPicked.length];
                if (nextVal !== undefined) {
                    const source = $('sortSource');
                    const tile = source
                        ? [...source.querySelectorAll('.sort-tile:not(.placed)')].find(tl => parseInt(tl.dataset.val) === nextVal)
                        : null;
                    if (tile) {
                        tile.style.outline = '3px solid #7c3aed';
                        tile.style.background = '#ede9fe';
                        setTimeout(() => { tile.style.outline = ''; tile.style.background = ''; }, 1500);
                    }
                }
            }
            showFeedback('💡 Hint used!', 'hint');
        } else if (type === 'extratime') {
            addExtraTime(10);
            showFeedback('⏱️ +10 seconds added!', 'hint');
        } else if (type === 'skip') {
            answered = true;
            showFeedback('⏭️ Round skipped!', 'hint');
            showNextButton();
        }
        renderPowerupsBar();
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: TRUE/FALSE
       Big statement + two giant TRUE / FALSE buttons
       Auto-advances on correct; shows next button on wrong
       ══════════════════════════════════════════════════════════ */
    function renderTrueFalse(round) {
        const area = $('gameArea');
        area.innerHTML = `
        <div class="tf-statement">
            ${round.emoji ? `<span class="tf-emoji">${round.emoji}</span>` : ''}
            <p class="tf-text">QUESTION: ${esc(round.statement)}</p>
        </div>
        <div class="tf-buttons">
            <button class="tf-btn tf-true"  data-ans="true">✅ TRUE</button>
            <button class="tf-btn tf-false" data-ans="false">❌ FALSE</button>
        </div>`;

        area.querySelectorAll('.tf-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (answered) return;
                const picked = btn.dataset.ans === 'true';
                answered = true;
                area.querySelectorAll('.tf-btn').forEach(b => b.classList.add('disabled'));
                if (picked === round.answer) {
                    btn.classList.add('correct');
                    recordCorrect();
                    const label = round.answer ? '✅ TRUE' : '❌ FALSE';
                    showFeedback('Correct! ' + label + ' is right!' + (round.explanation ? ' ' + round.explanation : ''), 'correct');
                    if (window.GuideCharacter) GuideCharacter.say('celebrating');
                    if (window.GameHelpers) GameHelpers.sparkleEffect();
                    setTimeout(() => { if (answered) { currentRound++; nextRound(); } }, 950);
                } else {
                    btn.classList.add('wrong');
                    const correctBtn = area.querySelector(`[data-ans="${round.answer}"]`);
                    if (correctBtn) correctBtn.classList.add('correct');
                    recordWrong();
                    const label = round.answer ? '✅ TRUE' : '❌ FALSE';
                    showFeedback(label + ' is the answer!' + (round.explanation ? ' ' + round.explanation : ''), 'wrong');
                    if (window.GuideCharacter) GuideCharacter.say('comforting');
                    showNextButton();
                }
            });
        });
    }

    /* ══════════════════════════════════════════════════════════
       RENDER: MATCH-PAIRS (Memory card flip game)
       Flip pairs of cards to find all matching pairs
       ══════════════════════════════════════════════════════════ */
    function renderMatchPairs(round) {
        const area       = $('gameArea');
        const pairs      = round.pairs;
        const totalPairs = pairs.length;

        // Override round tracking — whole game = one session
        totalRounds  = totalPairs;
        correctCount = 0;

        // Build shuffled card array
        const cards = [];
        pairs.forEach((pair, pairIdx) => {
            cards.push({ text: pair[0], pairId: pairIdx, cardId: cards.length });
            cards.push({ text: pair[1], pairId: pairIdx, cardId: cards.length });
        });
        shuffle(cards);

        let flipped      = [];         // at most 2 face-up cards awaiting check
        let locked       = false;      // prevent rapid taps during flip-back
        let matched      = new Set();  // card IDs permanently face-up
        let matchesFound = 0;
        const cols       = cards.length <= 6 ? 3 : 4;

        // No timer or attempts area for this game
        const timerWrap = $('gameTimerArea');
        if (timerWrap) timerWrap.innerHTML = '';
        const attWrap = $('gameAttemptsArea');
        if (attWrap) attWrap.innerHTML = '';
        $('gameActions').innerHTML = '';

        area.innerHTML = `
            <div class="match-grid" id="matchGrid" style="--cols:${cols}"></div>
            <div class="match-status" id="matchStatus">
                Find all <strong>${totalPairs}</strong> matching pairs!
            </div>`;

        const grid = $('matchGrid');

        cards.forEach(card => {
            const el = document.createElement('div');
            el.className = 'match-card';
            el.dataset.cardId = card.cardId;
            // Detect emoji-only content (all non-ASCII, no letters/digits/operators)
            const isEmojiOnly = /^[^\x00-\x7F\s]+$/.test(card.text.trim());
            const backClass = isEmojiOnly ? 'match-card-back emoji-only' : 'match-card-back';
            el.innerHTML = `<div class="match-card-inner">
                <div class="match-card-front">❓</div>
                <div class="${backClass}">${esc(card.text)}</div>
            </div>`;

            el.addEventListener('click', () => {
                if (answered || locked) return;
                if (matched.has(card.cardId)) return;
                if (flipped.find(c => c.cardId === card.cardId)) return;
                if (flipped.length >= 2) return;

                // Flip this card face-up
                el.classList.add('flipped');
                flipped.push({ cardId: card.cardId, pairId: card.pairId, el });

                if (flipped.length === 2) {
                    locked = true;
                    if (flipped[0].pairId === flipped[1].pairId) {
                        // ✅ Correct match!
                        matchesFound++;
                        correctCount++;
                        matched.add(flipped[0].cardId);
                        matched.add(flipped[1].cardId);
                        flipped[0].el.classList.add('matched');
                        flipped[1].el.classList.add('matched');
                        score += 100;
                        $('gScore').textContent = score;
                        $('gRound').textContent = matchesFound + '/' + totalPairs;
                        const combo = matchesFound >= 3 ? '🔥 x' + matchesFound + ' COMBO!' : null;
                        showFloatingScore(100, combo);
                        showStarFlash(3);
                        if (window.GuideCharacter) GuideCharacter.say('celebrating');
                        if (window.GameHelpers) GameHelpers.sparkleEffect();
                        $('matchStatus').innerHTML = `${matchesFound}/${totalPairs} matched! ${matchesFound === totalPairs ? '🎉' : ''}`;
                        flipped = [];
                        locked  = false;
                        if (matchesFound === totalPairs) {
                            answered = true;
                            setTimeout(() => endGame(), 700);
                        }
                    } else {
                        // ❌ No match — flip back after 900ms
                        const f0 = flipped[0], f1 = flipped[1];
                        flipped = [];
                        f0.el.classList.add('wrong-flip');
                        f1.el.classList.add('wrong-flip');
                        setTimeout(() => {
                            f0.el.classList.remove('flipped', 'wrong-flip');
                            f1.el.classList.remove('flipped', 'wrong-flip');
                            locked = false;
                        }, 900);
                        showFeedback('Not a match — keep looking! 🔍', 'wrong');
                    }
                }
            });

            grid.appendChild(el);
        });
    }

    /* ══════════════════════════════════════════════════════════
       HELPERS
       ══════════════════════════════════════════════════════════ */
    function buildSessionRounds(activity) {
        const sourceRounds = Array.isArray(activity?.rounds) ? activity.rounds : [];
        const rounds = sourceRounds.map(round => cloneRound(round));
        if (rounds.length <= 1) return rounds;

        // Teacher-created activities keep authored order; built-in activities are randomized.
        if (isTeacherActivity(activity)) {
            return rounds;
        }

        const order = buildShuffledOrder(rounds.length, String(activity.id || ''));
        const orderedRounds = order.map(idx => rounds[idx]);
        return orderedRounds.map(round => randomizeRoundContent(activity.type, round));
    }

    function randomizeRoundContent(type, round) {
        if (type === 'sort-order' && Array.isArray(round.items)) {
            round.items = shuffle([...round.items]);
            return round;
        }

        if (type === 'choose' && Array.isArray(round.options) && round.options.length > 1) {
            const answerIndex = Number(round.answer);
            if (Number.isInteger(answerIndex) && answerIndex >= 0 && answerIndex < round.options.length) {
                const zipped = round.options.map((option, idx) => ({ option, idx }));
                shuffle(zipped);
                round.options = zipped.map(item => item.option);
                round.answer = zipped.findIndex(item => item.idx === answerIndex);
            }
            return round;
        }

        return round;
    }

    function buildShuffledOrder(length, activityId) {
        if (length <= 1) return [0];
        const indices = Array.from({ length }, (_, i) => i);
        const previousOrder = _lastRoundOrderByActivity.get(activityId) || [];

        // Avoid reusing the exact same order on consecutive playthroughs.
        for (let attempts = 0; attempts < 6; attempts++) {
            shuffle(indices);
            if (!arraysEqual(indices, previousOrder)) break;
        }

        _lastRoundOrderByActivity.set(activityId, [...indices]);
        return indices;
    }

    function arraysEqual(a, b) {
        if (!Array.isArray(a) || !Array.isArray(b)) return false;
        if (a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) return false;
        }
        return true;
    }

    function cloneRound(round) {
        return JSON.parse(JSON.stringify(round));
    }

    function isTeacherActivity(activity) {
        return String(activity?.id || '').startsWith('ta-');
    }

    function shuffle(arr) {
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [arr[i], arr[j]] = [arr[j], arr[i]];
        }
        return arr;
    }

    function esc(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    function showFeedback(msg, type) {
        const fb = $('feedback');
        fb.textContent = msg;
        fb.className = 'feedback ' + type;
        fb.classList.remove('hidden');
        setTimeout(() => fb.classList.add('hidden'), 2000);
    }

    /* ── Fetch teacher timer setting ── */
    async function fetchTimerSetting() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/profile.php', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success && json.data && json.data.settings) {
                const val = json.data.settings.gameTimerSeconds;
                if (typeof val === 'number') GAME_TIMER_SECONDS = val;
                const sg = json.data.settings.showGameScore;
                if (typeof sg === 'boolean') showGameScore = sg;
            }
        } catch (_) {}
    }

    /* ── Fetch enabled games from teacher assignment API ── */
    async function fetchEnabledGames() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/student-games.php', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success && json.data && Array.isArray(json.data.enabled_game_ids)) {
                enabledGameIds = json.data.enabled_game_ids;
            }
        } catch (_) {
            // On failure, keep the defaults
        }
    }

    /* ── Fetch teacher-created activities and merge with BANK ── */
    async function fetchTeacherActivities() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        const normalizeCategory = (value) => {
            const raw = String(value || '').trim().toLowerCase();
            if (!raw) return 'selfcare';
            if (raw === 'self_care' || raw === 'self-care' || raw === 'self care') return 'selfcare';
            if (raw === 'filipino') return 'english';
            if (raw === 'math' || raw === 'english' || raw === 'selfcare') return raw;
            return 'selfcare';
        };
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/activities-student.php', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success && json.data && Array.isArray(json.data.teacher_activities)) {
                const teacherActivities = json.data.teacher_activities;
                // Convert teacher activities to BANK format and add them
                teacherActivities.forEach(ta => {
                    const converted = {
                        id: 'ta-' + ta.id,  // prefix to distinguish from defaults
                        subject: normalizeCategory(ta.category),
                        icon: ta.icon || '🎮',
                        title: ta.title,
                        desc: ta.description || 'Teacher activity',
                        type: ta.activity_type,
                        instruction: ta.instructions,
                        config: {},
                        rounds: ta.rounds || []  // already contains the items
                    };
                    BANK.push(converted);
                    // Add to enabled games so they show up
                    if (!enabledGameIds.includes(converted.id)) {
                        enabledGameIds.push(converted.id);
                    }
                });
            }
        } catch (err) {
            console.warn('Failed to load teacher activities:', err);
            // Continue with just default activities
        }
    }

    /* ── Nav stats ── */
    async function loadNavStats() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        try {
            const res = await fetch('../../EDUQUEST/api/gamification/profile.php', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success) {
                const p = json.data.profile;
                const navXp = $('navXp');
                const navStreak = $('navStreak');
                const navLevel  = $('navLevel');
                if (navXp) navXp.textContent = (p.totalXp || 0) + ' XP';
                if (navStreak) navStreak.textContent = (p.streakDays || 0) + ' days';
                if (navLevel) navLevel.textContent = 'Lv ' + (p.level || 1);
            }
        } catch (_) {}
    }

    /* ── Award XP ── */
    async function awardXP(xp) {
        if (xp <= 0) return;
        // Use the shared gamification helper for proper XP tracking:
        // updates localStorage cache, shows notifications (XP, level-up, egg, achievements),
        // and updates nav bar stats — so all pages reflect the new XP immediately.
        if (window.EduGamification) {
            const result = await EduGamification.trackActivity({
                activityType: 'activity',
                title: currentActivity.title + ' (' + currentActivity.subject + ')',
                score: Math.round((correctCount / totalRounds) * 100),
                maxScore: 100,
                attempts: 1,
            });
            // Update the result screen XP display with actual server-awarded amount
            if (result && result.success && result.data) {
                const rXP = $('rXP');
                if (rXP) rXP.textContent = '+' + result.data.xpAwarded + ' XP earned!';
            }
        } else {
            // Fallback: fire-and-forget if helper not loaded
            const token = localStorage.getItem('eq_token');
            if (!token) return;
            try {
                await fetch('../../EDUQUEST/api/gamification/track-activity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + token
                    },
                    body: JSON.stringify({
                        activityType: 'activity',
                        title: currentActivity.title + ' (' + currentActivity.subject + ')',
                        score: Math.round((correctCount / totalRounds) * 100),
                        maxScore: 100
                    })
                });
            } catch (_) {}
        }
    }

    /* ── Attempt Tracking ── */
    async function actStartAttempt() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        _actGameStartTime = Date.now();
        try {
            // Check if this is a teacher activity (starts with 'ta-')
            const isTeacherActivity = currentActivity && currentActivity.id && currentActivity.id.startsWith('ta-');
            
            if (isTeacherActivity) {
                // For teacher activities, use dedicated API
                const activityId = currentActivity.id.replace('ta-', '');
                const res = await fetch('../../EDUQUEST/api/attempt/teacher_activity_attempt.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({ action: 'start', activity_id: activityId }),
                });
                const json = await res.json();
                _actAttemptId = (json.data && json.data.attempt_id) ? json.data.attempt_id : 0;
            } else {
                // For predetermined games, use generic game API
                const res = await fetch('../../EDUQUEST/api/attempt/game_start.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({ game_type: 'activity' }),
                });
                const json = await res.json();
                _actAttemptId = (json.data && json.data.attempt_id) ? json.data.attempt_id : 0;
            }
        } catch (e) { _actAttemptId = 0; }
    }

    async function actCompleteAttempt(xpEarned) {
        if (_actAttemptId <= 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        const timeSpent = _actGameStartTime > 0 ? Math.round((Date.now() - _actGameStartTime) / 1000) : 0;
        const accuracy = totalRounds > 0 ? Math.round((correctCount / totalRounds) * 100) : 0;
        try {
            const isTeacherActivity = currentActivity && currentActivity.id && currentActivity.id.startsWith('ta-');
            
            if (isTeacherActivity) {
                // For teacher activities, use dedicated API
                await fetch('../../EDUQUEST/api/attempt/teacher_activity_attempt.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({
                        action: 'complete',
                        attempt_id: _actAttemptId,
                        score: score,
                        max_score: totalRounds * 100,
                        xp_earned: xpEarned,
                        time_spent_sec: timeSpent,
                    }),
                });
            } else {
                // For predetermined games, use generic game API
                await fetch('../../EDUQUEST/api/attempt/game_complete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
                    body: JSON.stringify({
                        attempt_id: _actAttemptId,
                        score: score,
                        max_score: totalRounds * 100,
                        xp_earned: xpEarned,
                        time_spent_sec: timeSpent,
                    }),
                });
            }
        } catch (e) { /* Silently fail */ }
        _actAttemptId = 0;
    }

    /* ── Activity History ── */
    async function fetchActivityHistory() {
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        try {
            const res = await fetch('../../EDUQUEST/api/attempt/my_attempts.php?type=game_list', {
                headers: { 'Authorization': 'Bearer ' + token }
            });
            const json = await res.json();
            if (json.success && json.data && json.data.games) {
                _actHistoryStats = json.data.games['activity'] || null;
                // Refresh hub stats panel if already on hub screen
                const histSection = $('actHistorySection');
                if (histSection && _actHistoryStats && _actHistoryStats.total_plays > 0) {
                    histSection.innerHTML = renderActivityHistoryHTML(_actHistoryStats);
                    histSection.classList.remove('hidden');
                }
            }
        } catch (e) { /* Silently fail */ }
    }

    function renderActivityHistoryHTML(stats) {
        const best  = stats.best_score  != null ? Math.round(stats.best_score) + '%' : '—';
        const last  = stats.last_played ? new Date(stats.last_played).toLocaleDateString() : '—';
        return `
            <div class="ac-history-box">
                <h4 class="ac-history-title">📋 My Game History</h4>
                <div class="ac-history-stats">
                    <div class="ac-hist-stat">
                        <span class="ac-hist-val">${stats.total_plays}</span>
                        <span class="ac-hist-lbl">Total Plays</span>
                    </div>
                    <div class="ac-hist-stat">
                        <span class="ac-hist-val">${stats.completed_plays}</span>
                        <span class="ac-hist-lbl">Completed</span>
                    </div>
                    <div class="ac-hist-stat">
                        <span class="ac-hist-val">${best}</span>
                        <span class="ac-hist-lbl">Best Score</span>
                    </div>
                    <div class="ac-hist-stat">
                        <span class="ac-hist-val ac-hist-date">${last}</span>
                        <span class="ac-hist-lbl">Last Played</span>
                    </div>
                </div>
            </div>`;
    }

    function actAbandonAttempt() {
        if (_actAttemptId <= 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;
        
        const isTeacherActivity = currentActivity && currentActivity.id && currentActivity.id.startsWith('ta-');
        const endpoint = isTeacherActivity 
            ? '../../EDUQUEST/api/attempt/teacher_activity_attempt.php'
            : '../../EDUQUEST/api/attempt/game_abandon.php';
        
        const payload = isTeacherActivity
            ? JSON.stringify({ action: 'abandon', attempt_id: _actAttemptId })
            : JSON.stringify({ attempt_id: _actAttemptId });
        
        navigator.sendBeacon
            ? navigator.sendBeacon(endpoint, new Blob([payload], { type: 'application/json' }))
            : fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }, body: payload, keepalive: true }).catch(() => {});
        _actAttemptId = 0;
    }

    /* ── Submit quest grade to teacher portal (real-time grading) ── */
    async function submitQuestGrade() {
        if (!currentActivity || totalRounds === 0) return;
        const token = localStorage.getItem('eq_token');
        if (!token) return;

        const accuracy = totalRounds > 0 ? Math.round((correctCount / totalRounds) * 100) : 0;
        const typeMap = {
            choose: 'quiz',
            classify: 'quiz',
            compare: 'quiz',
            'sort-order': 'quiz',
            'build-word': 'assignment'
        };

        try {
            const res = await fetch('../../EDUQUEST/api/students/submit-quest-grade.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + token
                },
                body: JSON.stringify({
                    assessment_name: currentActivity.title,
                    assessment_type: typeMap[currentActivity.type] || 'quiz',
                    score: accuracy,
                    max_score: 100,
                    remarks: 'Auto-recorded from quest: ' + correctCount + '/' + totalRounds + ' correct'
                })
            });
            const json = await res.json();
            if (!json.success) {
                console.warn('Quest grade submission failed:', json.message);
            }
        } catch (err) {
            console.warn('Quest grade submission error:', err);
        }
    }

    /* ── Logout ── */
    window.handleLogout = async function () {
        const t = localStorage.getItem('eq_token');
        if (t) {
            await fetch('../../EDUQUEST/api/auth/logout.php', {
                method: 'POST',
                headers: { Authorization: 'Bearer ' + t },
                credentials: 'include',
            }).catch(() => {});
        }
        ['eq_token', 'eq_teacher', 'eq_student', 'eduquest_user',
         'student_progress', 'eduquest_remember_me'].forEach(k =>
            localStorage.removeItem(k)
        );
        sessionStorage.removeItem('user_id');
        sessionStorage.removeItem('user_role');
        window.location.href = '../../auth/login/login.html?role=student';
    };

})();
