/* ═══════════════════════════════════════════════════════════════
   Activity Builder — Teacher Dashboard
   CRUD operations for custom gamified learning activities
   ═══════════════════════════════════════════════════════════════ */

(() => {
    'use strict';

    const API_BASE = '../api/gamification/activities.php';
    const COURSES_API = '../api/courses/list.php';
    const STUDENTS_API = '../api/students/list.php';
    const TYPE_LABELS = {
        'sort-order': 'Sort / Order',
        'classify': 'Classify / Sort Categories',
        'compare': 'Compare',
        'choose': 'Multiple Choice',
        'build-word': 'Build Word',
        'truefalse': 'True or False',
        'match-pairs': 'Match Pairs'
    };
    const TYPE_OPTIONS_BY_CATEGORY = {
        math: ['sort-order', 'compare', 'choose', 'truefalse', 'match-pairs'],
        english: ['build-word', 'choose', 'truefalse', 'match-pairs'],
        selfcare: ['classify', 'choose', 'truefalse', 'match-pairs']
    };
    let currentActivityId = null;
    let editingMode = false;
    let assignStudentsCache = [];
    let defaultGamesCatalog = [];

    // ── DOM Elements ──
    const listView = document.getElementById('listView');
    const builderView = document.getElementById('builderView');
    const activityList = document.getElementById('activityList');
    const activityEmpty = document.getElementById('activityEmpty');

    const btnNewActivity = document.getElementById('btnNewActivity');
    const btnBackToList = document.getElementById('btnBackToList');
    const btnSaveActivity = document.getElementById('btnSaveActivity');
    const btnConfirmDuplicate = document.getElementById('btnConfirmDuplicate');

    const actTitle = document.getElementById('actTitle');
    const actCategory = document.getElementById('actCategory');
    const actIcon = document.getElementById('actIcon');
    const actDescription = document.getElementById('actDescription');
    const actType = document.getElementById('actType');
    const actRounds = document.getElementById('actRounds');
    const actInstructions = document.getElementById('actInstructions');
    const actXP = document.getElementById('actXP');
    const actPassPercentage = document.getElementById('actPassPercentage');
    const actMaxAttempts = document.getElementById('actMaxAttempts');
    const actTimeLimit = document.getElementById('actTimeLimit');
    const itemsList = document.getElementById('itemsList');
    const btnAddItem = document.getElementById('btnAddItem');

    const activitySearch = document.getElementById('activitySearch');
    const activityCategoryFilter = document.getElementById('activityCategoryFilter');

    const logoutBtn = document.getElementById('logoutBtn');
    let previousActivityType = actType.value;

    // ── Event Listeners ──
    btnNewActivity.addEventListener('click', resetForm);
    btnBackToList.addEventListener('click', (e) => { e.preventDefault(); showListView(); });
    btnSaveActivity.addEventListener('click', saveActivity);
    btnAddItem.addEventListener('click', addItemRow);
    actType.addEventListener('change', onActivityTypeChanged);
    actCategory.addEventListener('change', onCategoryChanged);
    activitySearch.addEventListener('input', loadActivities);
    activityCategoryFilter.addEventListener('change', loadActivities);
    btnConfirmDuplicate.addEventListener('click', duplicateCurrentActivity);
    // auth-guard.js owns logout behavior for teacher dashboard pages.
    if (logoutBtn && !(window.EQ && typeof window.EQ.authHeaders === 'function')) {
        logoutBtn.addEventListener('click', handleLogout);
    }

    // Tabs
    document.querySelectorAll('.qb-detail-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = tab.dataset.tab;
            switchTab(tabName);
        });
    });

    // ── Initialization ──
    loadTeacherInfo();
    loadActivities();
    syncTypeOptionsWithCategory(actType.value);

    // ════════════════════════════════════════════════════════════
    // LOAD & DISPLAY ACTIVITIES
    // ════════════════════════════════════════════════════════════

    function loadActivities() {
        const search = activitySearch.value.trim();
        const category = activityCategoryFilter.value;

        let url = `${API_BASE}?action=list`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (category) url += `&category=${encodeURIComponent(category)}`;

        activityList.innerHTML = '<div class="loading-msg">Loading activities…</div>';

        fetch(url, { headers: authHeaders(false) })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);

                const activities = json.data.activities || [];
                displayActivities(activities);
            })
            .catch(err => {
                console.error('Failed to load activities:', err);
                activityList.innerHTML = `<div class="error-msg">Error loading activities: ${err.message}</div>`;
            });
    }

    function displayActivities(activities) {
        if (activities.length === 0) {
            activityList.innerHTML = '';
            activityEmpty.classList.remove('hidden');
            return;
        }

        activityEmpty.classList.add('hidden');
        activityList.innerHTML = '';

        activities.forEach(activity => {
            const categoryIcons = { math: '🔢', english: '📖', selfcare: '🌱' };
            const icon = categoryIcons[activity.category] || '🎮';
            const statusBadge = activity.is_active ? '<span class="badge badge-success">Active</span>' : '<span class="badge badge-secondary">Inactive</span>';
            const nextToggleState = activity.is_active ? 0 : 1;
            const toggleTitle = activity.is_active ? 'Deactivate activity' : 'Activate activity';

            const card = document.createElement('div');
            card.className = 'qb-quiz-card';
            card.innerHTML = `
                <div class="qqc-header">
                    <div class="qqc-title-group">
                        <span class="qqc-icon">${activity.icon || icon}</span>
                        <div>
                            <h3 class="qqc-title">${escapeHtml(activity.title)}</h3>
                            <p class="qqc-subtitle">${escapeHtml(activity.description || 'No description')}</p>
                        </div>
                    </div>
                    <div class="qqc-status">${statusBadge}</div>
                </div>
                <div class="qqc-meta">
                    <span class="meta-pill">🎮 ${activity.activity_type}</span>
                    <span class="meta-pill">📝 ${activity.item_count || 0} items</span>
                    <span class="meta-pill">👥 ${activity.attempt_count || 0} attempts</span>
                </div>
                <div class="qqc-actions">
                    <button class="btn btn-sm btn-outline qqc-icon-btn" title="${toggleTitle}" aria-label="${toggleTitle}" onclick="event.stopPropagation(); toggleActivity(${activity.id}, ${nextToggleState})">${activity.is_active ? '⏸️' : '▶️'}</button>
                    <button class="btn btn-sm btn-outline qqc-icon-btn danger" title="Delete activity" aria-label="Delete activity" onclick="event.stopPropagation(); deleteActivity(${activity.id})">🗑️</button>
                </div>
            `;
            card.addEventListener('click', () => editActivity(activity.id, 'edit'));
            activityList.appendChild(card);
        });
    }

    // ════════════════════════════════════════════════════════════
    // EDIT / CREATE ACTIVITY
    // ════════════════════════════════════════════════════════════

    window.editActivity = function(id, startTab = 'edit') {
        currentActivityId = id;
        editingMode = true;

        fetch(`${API_BASE}?action=get&id=${id}`, { headers: authHeaders(false) })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);

                const activity = json.data.activity;
                const items = json.data.items || [];

                // Populate form
                actTitle.value = activity.title || '';
                actCategory.value = activity.category || '';
                actIcon.value = activity.icon || '🎮';
                actDescription.value = activity.description || '';
                const resolvedType = syncTypeOptionsWithCategory(activity.activity_type || 'sort-order');
                previousActivityType = resolvedType;
                actRounds.value = activity.rounds || 6;
                actInstructions.value = activity.instructions || '';
                actXP.value = activity.xp_reward || 50;
                actPassPercentage.value = activity.pass_percentage || 70;
                actMaxAttempts.value = activity.max_attempts || 0;
                actTimeLimit.value = activity.time_limit_sec || 0;

                // Populate items
                itemsList.innerHTML = '';
                items.forEach((item, idx) => {
                    let itemData = item.item_data;
                    if (typeof itemData === 'string') {
                        try {
                            itemData = JSON.parse(itemData);
                        } catch (e) {
                            itemData = {};
                        }
                    }
                    addItemRow(itemData);
                });
                if (items.length === 0) addItemRow();

                // Show builder view with tabs
                document.getElementById('builderTitle').textContent = `Edit: ${escapeHtml(activity.title)}`;
                document.getElementById('activityDetailTabs').classList.remove('hidden');
                showBuilderView();
                switchTab(startTab);
            })
            .catch(err => {
                console.error('Failed to load activity:', err);
                alert('Failed to load activity: ' + err.message);
            });
    };

    function resetForm() {
        currentActivityId = null;
        editingMode = false;

        actTitle.value = '';
        actCategory.value = '';
        actIcon.value = '🎮';
        actDescription.value = '';
        const resolvedType = syncTypeOptionsWithCategory('sort-order');
        previousActivityType = resolvedType;
        actRounds.value = 6;
        actInstructions.value = '';
        actXP.value = 50;
        actPassPercentage.value = 70;
        actMaxAttempts.value = 0;
        actTimeLimit.value = 0;
        itemsList.innerHTML = '';

        document.getElementById('builderTitle').textContent = 'Create New Activity';
        document.getElementById('activityDetailTabs').classList.add('hidden');

        // Add at least one empty item row
        addItemRow();

        showBuilderView();
        switchTab('edit');
    }

    function addItemRow(data = {}) {
        const itemNum = itemsList.children.length + 1;
        const type = actType.value;
        const itemRow = document.createElement('div');
        itemRow.className = 'qb-question-item activity-item-row';
        itemRow.innerHTML = `
            <div class="qb-question-header">
                <span class="qb-question-num">Item ${itemNum}</span>
                <button type="button" class="btn btn-sm btn-danger remove-item-btn">🗑️</button>
            </div>
            ${buildItemEditor(type, data)}
        `;

        itemRow.querySelector('.remove-item-btn').addEventListener('click', () => {
            itemRow.remove();
            renumberItems();
            if (itemsList.children.length === 0) addItemRow();
        });

        if (type === 'match-pairs') {
            const list = itemRow.querySelector('.pairs-list');
            const pairs = Array.isArray(data.pairs) && data.pairs.length ? data.pairs : [['', '']];
            pairs.forEach(pair => appendPairRow(list, pair[0], pair[1]));

            itemRow.querySelector('.add-pair-btn').addEventListener('click', () => appendPairRow(list, '', ''));
            list.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-pair-btn')) {
                    const row = e.target.closest('.pair-row');
                    row.remove();
                    if (list.children.length === 0) appendPairRow(list, '', '');
                }
            });
        }

        itemsList.appendChild(itemRow);
    }

    function buildItemEditor(type, data) {
        if (type === 'sort-order') {
            return `
                <div class="form-group">
                    <label>Values to Sort</label>
                    <input type="text" class="form-input so-items" value="${escapeHtml(arrayToCsv(data.items))}" placeholder="Example: 5, 2, 8, 1, 3" />
                    <small class="text-muted">Comma-separated values for one round.</small>
                </div>
            `;
        }
        if (type === 'classify') {
            return `
                <div class="form-row">
                    <div class="form-group" style="flex:0.8">
                        <label>Emoji</label>
                        <input type="text" class="form-input classify-emoji" value="${escapeHtml(data.emoji || '')}" placeholder="🐕" />
                    </div>
                    <div class="form-group" style="flex:2">
                        <label>Label</label>
                        <input type="text" class="form-input classify-label" value="${escapeHtml(data.label || '')}" placeholder="Dog" />
                    </div>
                    <div class="form-group" style="flex:1.4">
                        <label>Category Answer</label>
                        <input type="text" class="form-input classify-answer" value="${escapeHtml(data.answer || '')}" placeholder="living" />
                    </div>
                </div>
            `;
        }
        if (type === 'compare') {
            return `
                <div class="form-row">
                    <div class="form-group" style="flex:1">
                        <label>Left Value</label>
                        <input type="number" class="form-input compare-left" value="${escapeHtml(asText(data.left ?? ''))}" />
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>Right Value</label>
                        <input type="number" class="form-input compare-right" value="${escapeHtml(asText(data.right ?? ''))}" />
                    </div>
                    <div class="form-group" style="flex:1">
                        <label>Correct Symbol</label>
                        <select class="form-input compare-answer">
                            <option value="<" ${data.answer === '<' ? 'selected' : ''}>&lt;</option>
                            <option value="=" ${data.answer === '=' ? 'selected' : ''}>=</option>
                            <option value=">" ${data.answer === '>' ? 'selected' : ''}>&gt;</option>
                        </select>
                    </div>
                </div>
            `;
        }
        if (type === 'choose') {
            const opts = Array.isArray(data.options) && data.options.length === 4 ? data.options : ['', '', '', ''];
            const answerIndex = Number.isInteger(data.answer) ? data.answer : 0;
            return `
                <div class="form-group">
                    <label>Prompt Emoji (optional)</label>
                    <input type="text" class="form-input choose-emoji" value="${escapeHtml(data.emoji || '')}" placeholder="🥇" />
                </div>
                <div class="form-group">
                    <label>Question</label>
                    <textarea class="form-input choose-question" rows="2" placeholder="Type your question...">${escapeHtml(data.question || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>Options</label>
                    ${opts.map((opt, idx) => `
                        <input type="text" class="form-input choose-opt" data-opt-index="${idx}" value="${escapeHtml(opt || '')}" placeholder="Option ${idx + 1}" style="margin-bottom:.5rem" />
                    `).join('')}
                    <label style="margin-top:.25rem">Correct Option</label>
                    <select class="form-input choose-answer-index">
                        <option value="0" ${answerIndex === 0 ? 'selected' : ''}>Option 1</option>
                        <option value="1" ${answerIndex === 1 ? 'selected' : ''}>Option 2</option>
                        <option value="2" ${answerIndex === 2 ? 'selected' : ''}>Option 3</option>
                        <option value="3" ${answerIndex === 3 ? 'selected' : ''}>Option 4</option>
                    </select>
                </div>
            `;
        }
        if (type === 'build-word') {
            return `
                <div class="form-group">
                    <label>Target Word</label>
                    <input type="text" class="form-input bw-word" value="${escapeHtml(data.word || '')}" placeholder="SIT" />
                </div>
                <div class="form-group">
                    <label>Hint</label>
                    <input type="text" class="form-input bw-hint" value="${escapeHtml(data.hint || '')}" placeholder="🪑 Something you do on a chair" />
                </div>
                <div class="form-group">
                    <label>Extra Letters</label>
                    <input type="text" class="form-input bw-extras" value="${escapeHtml(arrayToCsv(data.extras))}" placeholder="A, O" />
                </div>
            `;
        }
        if (type === 'truefalse') {
            return `
                <div class="form-group">
                    <label>Emoji (optional)</label>
                    <input type="text" class="form-input tf-emoji" value="${escapeHtml(data.emoji || '')}" placeholder="➕" />
                </div>
                <div class="form-group">
                    <label>Statement</label>
                    <textarea class="form-input tf-statement" rows="2" placeholder="5 + 3 = 8">${escapeHtml(data.statement || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>Correct Answer</label>
                    <select class="form-input tf-answer">
                        <option value="true" ${data.answer === true ? 'selected' : ''}>True</option>
                        <option value="false" ${data.answer === false ? 'selected' : ''}>False</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Explanation</label>
                    <textarea class="form-input tf-explanation" rows="2" placeholder="Explain why the answer is correct.">${escapeHtml(data.explanation || '')}</textarea>
                </div>
            `;
        }
        if (type === 'match-pairs') {
            return `
                <div class="form-group">
                    <label>Pairs</label>
                    <div class="pairs-list"></div>
                    <button type="button" class="btn btn-sm btn-outline add-pair-btn" style="margin-top:.5rem">+ Add Pair</button>
                </div>
            `;
        }

        return `
            <div class="form-group">
                <label>Item Label / Prompt <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-input custom-label" value="${escapeHtml(data.label || '')}" placeholder="e.g. What is 2 + 2?" />
            </div>
            <div class="form-group">
                <label>Correct Answer <span style="color:#ef4444">*</span></label>
                <input type="text" class="form-input custom-answer" value="${escapeHtml(data.answer !== undefined ? String(data.answer) : '')}" placeholder="e.g. 4" />
            </div>
            <div class="form-group">
                <label>Hint / Extra Info <span class="muted">(optional)</span></label>
                <input type="text" class="form-input custom-hint" value="${escapeHtml(data.hint || '')}" placeholder="e.g. Think about addition" />
            </div>
        `;
    }

    function appendPairRow(container, left, right) {
        const row = document.createElement('div');
        row.className = 'pair-row';
        row.style.cssText = 'display:flex;gap:.5rem;align-items:center;margin-bottom:.5rem;';
        row.innerHTML = `
            <input type="text" class="form-input pair-left" value="${escapeHtml(asText(left || ''))}" placeholder="Side A" />
            <input type="text" class="form-input pair-right" value="${escapeHtml(asText(right || ''))}" placeholder="Side B" />
            <button type="button" class="btn btn-sm btn-danger remove-pair-btn">×</button>
        `;
        container.appendChild(row);
    }

    function collectItems() {
        const type = actType.value;
        const rows = [...itemsList.querySelectorAll('.activity-item-row')];
        if (rows.length === 0) {
            showAlert('Add at least one item.', 'error');
            return null;
        }

        try {
            return rows.map(row => readRowData(row, type));
        } catch (e) {
            showAlert(e.message, 'error');
            return null;
        }
    }

    function readRowData(row, type) {
        if (type === 'sort-order') {
            const values = csvToArray(row.querySelector('.so-items').value).map(parseScalarValue);
            if (values.length === 0) throw new Error('Each sort-order item needs at least one value.');
            return { items: values };
        }

        if (type === 'classify') {
            const emoji = row.querySelector('.classify-emoji').value.trim();
            const label = row.querySelector('.classify-label').value.trim();
            const answer = row.querySelector('.classify-answer').value.trim();
            if (!label || !answer) throw new Error('Classify items require label and answer.');
            return { emoji, label, answer };
        }

        if (type === 'compare') {
            const leftRaw = row.querySelector('.compare-left').value;
            const rightRaw = row.querySelector('.compare-right').value;
            if (leftRaw === '' || rightRaw === '') throw new Error('Compare items require both values.');
            return {
                left: Number(leftRaw),
                right: Number(rightRaw),
                answer: row.querySelector('.compare-answer').value
            };
        }

        if (type === 'choose') {
            const options = [...row.querySelectorAll('.choose-opt')].map(el => el.value.trim());
            const question = row.querySelector('.choose-question').value.trim();
            if (!question) throw new Error('Multiple-choice items require a question.');
            if (options.some(o => !o)) throw new Error('Fill all multiple-choice options.');
            return {
                emoji: row.querySelector('.choose-emoji').value.trim(),
                question,
                options,
                answer: Number(row.querySelector('.choose-answer-index').value)
            };
        }

        if (type === 'build-word') {
            const word = row.querySelector('.bw-word').value.trim().toUpperCase();
            if (!word) throw new Error('Build-word items require a target word.');
            return {
                word,
                hint: row.querySelector('.bw-hint').value.trim(),
                extras: csvToArray(row.querySelector('.bw-extras').value).map(s => s.toUpperCase())
            };
        }

        if (type === 'truefalse') {
            const statement = row.querySelector('.tf-statement').value.trim();
            if (!statement) throw new Error('True/False items require a statement.');
            return {
                emoji: row.querySelector('.tf-emoji').value.trim(),
                statement,
                answer: row.querySelector('.tf-answer').value === 'true',
                explanation: row.querySelector('.tf-explanation').value.trim()
            };
        }

        if (type === 'match-pairs') {
            const pairs = [...row.querySelectorAll('.pair-row')]
                .map(pairRow => [
                    pairRow.querySelector('.pair-left').value.trim(),
                    pairRow.querySelector('.pair-right').value.trim()
                ])
                .filter(pair => pair[0] && pair[1]);
            if (pairs.length === 0) throw new Error('Match-pairs items require at least one pair.');
            return { pairs };
        }

        const text = row.querySelector('.item-data')?.value?.trim();
        if (text !== undefined) {
            if (!text) return {};
            return JSON.parse(text);
        }
        // custom type friendly editor
        const label = row.querySelector('.custom-label')?.value?.trim() || '';
        const answer = row.querySelector('.custom-answer')?.value?.trim() || '';
        const hint = row.querySelector('.custom-hint')?.value?.trim() || '';
        if (!label) throw new Error('Custom items require a label/prompt.');
        if (!answer) throw new Error('Custom items require a correct answer.');
        return { label, answer, ...(hint && { hint }) };
    }

    function renumberItems() {
        [...itemsList.querySelectorAll('.qb-question-num')].forEach((el, idx) => {
            el.textContent = `Item ${idx + 1}`;
        });
    }

    function onActivityTypeChanged() {
        if (itemsList.children.length === 0) {
            previousActivityType = actType.value;
            addItemRow();
            return;
        }

        if (!confirm('Changing activity type will reset existing item editors. Continue?')) {
            actType.value = previousActivityType;
            return;
        }

        previousActivityType = actType.value;
        itemsList.innerHTML = '';
        addItemRow();
    }

    function onCategoryChanged() {
        const priorType = actType.value;
        const resolvedType = syncTypeOptionsWithCategory(priorType);
        previousActivityType = resolvedType;

        if (resolvedType !== priorType && itemsList.children.length > 0) {
            itemsList.innerHTML = '';
            addItemRow();
            showAlert('Activity type options updated for this category.', 'success');
        }
    }

    function syncTypeOptionsWithCategory(preferredType) {
        const category = (actCategory.value || '').toLowerCase();
        const allowedTypes = TYPE_OPTIONS_BY_CATEGORY[category] || Object.keys(TYPE_LABELS);

        actType.innerHTML = allowedTypes
            .map(type => `<option value="${type}">${TYPE_LABELS[type] || type}</option>`)
            .join('');

        const resolvedType = allowedTypes.includes(preferredType) ? preferredType : allowedTypes[0];
        actType.value = resolvedType;
        return resolvedType;
    }

    function saveActivity() {
        // Validate
        if (!actTitle.value.trim()) {
            showAlert('Activity Title is required.', 'error');
            actTitle.focus();
            return;
        }
        if (!actCategory.value) {
            showAlert('Category is required.', 'error');
            actCategory.focus();
            return;
        }

        // Collect items
        const items = collectItems();
        if (!items) return;

        const payload = {
            action: editingMode ? 'update' : 'create',
            ...(editingMode && { id: currentActivityId }),
            title: actTitle.value,
            description: actDescription.value,
            category: actCategory.value,
            icon: actIcon.value || '🎮',
            activity_type: actType.value,
            instructions: actInstructions.value,
            rounds: parseInt(actRounds.value),
            xp_reward: parseInt(actXP.value),
            pass_percentage: parseInt(actPassPercentage.value),
            max_attempts: parseInt(actMaxAttempts.value),
            time_limit_sec: parseInt(actTimeLimit.value),
            items: items
        };

        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify(payload)
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);

                showAlert(editingMode ? 'Activity updated successfully!' : 'Activity created successfully!', 'success');
                setTimeout(() => {
                    showListView();
                    loadActivities();
                }, 1000);
            })
            .catch(err => {
                console.error('Failed to save activity:', err);
                showAlert(`Failed to save: ${err.message}`, 'error');
            });
    }

    // ════════════════════════════════════════════════════════════
    // DELETE ACTIVITY
    // ════════════════════════════════════════════════════════════

    window.deleteActivity = function(id) {
        if (!confirm('Are you sure? This cannot be undone.')) return;

        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify({ action: 'delete', id })
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                showAlert('Activity deleted.', 'success');
                loadActivities();
            })
            .catch(err => {
                console.error('Failed to delete activity:', err);
                showAlert(`Failed to delete: ${err.message}`, 'error');
            });
    };

    window.toggleActivity = function(id, isActive) {
        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify({ action: 'toggle', id, is_active: isActive })
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                loadActivities();
            })
            .catch(err => {
                console.error('Failed to toggle activity:', err);
                showAlert(`Failed to toggle: ${err.message}`, 'error');
            });
    };

    // ════════════════════════════════════════════════════════════
    // HELPER FUNCTIONS
    // ════════════════════════════════════════════════════════════

    function showListView() {
        listView.classList.remove('hidden');
        builderView.classList.add('hidden');
    }

    function showBuilderView() {
        listView.classList.add('hidden');
        builderView.classList.remove('hidden');
    }

    function switchTab(tabName) {
        // Update tabs
        document.querySelectorAll('.qb-detail-tab').forEach(t => t.classList.remove('active'));
        document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');

        // Update panes
        document.querySelectorAll('.qb-tab-pane').forEach(p => p.classList.add('hidden'));
        document.getElementById(`activityPane-${tabName}`).classList.remove('hidden');

        // Load content if needed
        if (tabName === 'results' && currentActivityId) {
            loadResults();
        } else if (tabName === 'assign' && currentActivityId) {
            loadAssignUI();
        }
    }

    function loadResults() {
        fetch(`${API_BASE}?action=results&id=${currentActivityId}`, { headers: authHeaders(false) })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);

                const data = json.data;
                let html = `
                    <div class="stats-row" style="margin-bottom:2rem;">
                        <div class="stat-card sc-blue">
                            <div class="sc-icon">👥</div>
                            <div class="sc-body">
                                <div class="stat-value">${data.total_attempts}</div>
                                <div class="stat-label">Total Attempts</div>
                            </div>
                        </div>
                        <div class="stat-card sc-green">
                            <div class="sc-icon">✅</div>
                            <div class="sc-body">
                                <div class="stat-value">${data.passed_count}</div>
                                <div class="stat-label">Passed</div>
                            </div>
                        </div>
                        <div class="stat-card sc-amber">
                            <div class="sc-icon">📊</div>
                            <div class="sc-body">
                                <div class="stat-value">${data.average_score.toFixed(1)}%</div>
                                <div class="stat-label">Avg Score</div>
                            </div>
                        </div>
                    </div>
                `;

                if (data.attempts && data.attempts.length > 0) {
                    html += '<div class="table-wrapper"><table class="data-table"><thead><tr><th>Student</th><th>Attempt</th><th>Score</th><th>Time</th><th>Date</th></tr></thead><tbody>';
                    data.attempts.forEach(a => {
                        const pct = Number.parseFloat(a.percentage);
                        const scoreText = Number.isFinite(pct) ? `${pct.toFixed(1)}%` : '0.0%';
                        html += `<tr>
                            <td>${escapeHtml(a.first_name + ' ' + a.last_name)}</td>
                            <td>${a.attempt_number}</td>
                            <td>${scoreText}</td>
                            <td>${Math.round(a.time_spent_sec / 60)}m</td>
                            <td>${new Date(a.completed_at).toLocaleDateString()}</td>
                        </tr>`;
                    });
                    html += '</tbody></table></div>';
                }

                document.getElementById('resultsContent').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('resultsContent').innerHTML = `<div class="error-msg">Error loading results: ${err.message}</div>`;
            });
    }

    function loadAssignUI() {
        const html = `
            <div id="assignAlert" class="alert hidden" style="margin-bottom:1rem"></div>

            <!-- Scope selector -->
            <div class="assign-scope-row">
                <label class="assign-scope-opt">
                    <input type="radio" name="assignScope" value="course" checked />
                    <span>&#127979; Entire Course</span>
                </label>
                <label class="assign-scope-opt">
                    <input type="radio" name="assignScope" value="students" />
                    <span>&#128100; Specific Students</span>
                </label>
            </div>

            <!-- Course + Due Date -->
            <div class="assign-fields-row">
                <div class="form-group" style="flex:1">
                    <label for="assignCourse">Course</label>
                    <select id="assignCourse" class="form-input"><option value="">— Select Course —</option></select>
                </div>
                <div class="form-group" style="flex:1">
                    <label for="assignDueDate">Due Date <span class="muted">(optional)</span></label>
                    <input type="date" id="assignDueDate" class="form-input" />
                </div>
            </div>

            <!-- Student picker (shown only when scope = students) -->
            <div id="assignStudentSection" class="assign-student-section hidden">
                <input type="text" id="assignStudentSearch" class="form-input" placeholder="&#128269; Search students…" style="margin-bottom:.5rem" />
                <div class="assign-list-controls">
                    <label style="display:flex;align-items:center;gap:.4rem;font-size:.85rem">
                        <input type="checkbox" id="assignSelectAll" /> Select all
                    </label>
                    <span id="assignMatchCount" class="muted" style="font-size:.82rem"></span>
                </div>
                <div id="assignStudentList" class="assign-student-list">
                    <div class="loading-msg">Loading students…</div>
                </div>
            </div>

            <!-- Single action button -->
            <div style="display:flex;justify-content:flex-end;margin-top:1rem">
                <button type="button" class="btn btn-primary" id="btnAssign">&#10003; Assign Activity</button>
            </div>

            <hr style="margin:1.25rem 0;border:none;border-top:1px solid #e5e7eb" />

            <!-- Quest game toggles -->
            <div class="assign-games-section">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.65rem">
                    <div>
                        <strong style="font-size:.97rem">&#127918; Quest Games</strong>
                        <p class="muted" style="margin:.1rem 0 0;font-size:.82rem">Enable or disable built-in quest games for students.</p>
                    </div>
                    <div style="display:flex;gap:.45rem">
                        <button type="button" class="btn btn-sm btn-outline" id="btnDefaultGamesAllOn">All On</button>
                        <button type="button" class="btn btn-sm btn-outline" id="btnDefaultGamesAllOff">All Off</button>
                        <button type="button" class="btn btn-sm btn-primary" id="btnSaveDefaultGames">Save</button>
                    </div>
                </div>
                <div id="defaultGamesToggleList" class="assign-games-grid">
                    <div class="loading-msg">Loading…</div>
                </div>
            </div>
        `;
        const root = document.getElementById('assignContent');
        root.innerHTML = html;

        // Scope radio toggle
        const radios = root.querySelectorAll('input[name="assignScope"]');
        const studentSection = document.getElementById('assignStudentSection');
        radios.forEach(r => r.addEventListener('change', () => {
            studentSection.classList.toggle('hidden', r.value === 'course' && r.checked ||
                ![...radios].find(x => x.value === 'students')?.checked);
        }));
        // Ensure correct initial state
        root.querySelector('input[value="students"]').addEventListener('change', () => studentSection.classList.remove('hidden'));
        root.querySelector('input[value="course"]').addEventListener('change', () => studentSection.classList.add('hidden'));

        Promise.all([
            fetch(COURSES_API, { headers: authHeaders(false) }).then(res => res.json()),
            fetch(STUDENTS_API, { headers: authHeaders(false) }).then(res => res.json())
        ])
            .then(([coursesJson, studentsJson]) => {
                const courseSelect = document.getElementById('assignCourse');
                const courses = (coursesJson.success && coursesJson.data && coursesJson.data.courses) ? coursesJson.data.courses : [];
                courses.forEach(c => {
                    const opt = document.createElement('option');
                    opt.value = c.id;
                    opt.textContent = c.title;
                    courseSelect.appendChild(opt);
                });

                assignStudentsCache = (studentsJson.success && studentsJson.data && studentsJson.data.students) ? studentsJson.data.students : [];
                renderAssignStudents('');

                const search = document.getElementById('assignStudentSearch');
                const selectAll = document.getElementById('assignSelectAll');
                search.addEventListener('input', () => {
                    renderAssignStudents(search.value);
                    selectAll.checked = false;
                });
                selectAll.addEventListener('change', () => {
                    document.querySelectorAll('.assign-student-check').forEach(cb => { cb.checked = selectAll.checked; });
                });

                document.getElementById('btnAssign').addEventListener('click', () => {
                    const isStudentScope = [...radios].find(r => r.value === 'students')?.checked;
                    if (isStudentScope) {
                        const checked = [...document.querySelectorAll('.assign-student-check:checked')].map(cb => parseInt(cb.value, 10));
                        if (checked.length === 0) {
                            const assignAlert = document.getElementById('assignAlert');
                            assignAlert.textContent = '❌ Select at least one student, or switch to “Entire Course”.';
                            assignAlert.className = 'alert alert-danger';
                            assignAlert.classList.remove('hidden');
                            return;
                        }
                        submitAssignment(checked);
                    } else {
                        submitAssignment([]);
                    }
                });

                loadDefaultGamesToggles();
            })
            .catch(err => {
                root.innerHTML = `<div class="error-msg">Failed to load assignment tools: ${escapeHtml(err.message)}</div>`;
            });
    }

    function loadDefaultGamesToggles() {
        const list = document.getElementById('defaultGamesToggleList');
        if (!list) return;

        fetch(`${API_BASE}?action=default-games`, { headers: authHeaders(false) })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                defaultGamesCatalog = (json.data && Array.isArray(json.data.games)) ? json.data.games : [];
                renderDefaultGamesToggleList();

                const allOnBtn = document.getElementById('btnDefaultGamesAllOn');
                const allOffBtn = document.getElementById('btnDefaultGamesAllOff');
                const saveBtn = document.getElementById('btnSaveDefaultGames');

                allOnBtn.addEventListener('click', () => setDefaultGameToggles(true));
                allOffBtn.addEventListener('click', () => setDefaultGameToggles(false));
                saveBtn.addEventListener('click', saveDefaultGamesToggles);
            })
            .catch(err => {
                list.innerHTML = `<p class="error-msg">Failed to load game toggles: ${escapeHtml(err.message)}</p>`;
            });
    }

    function renderDefaultGamesToggleList() {
        const list = document.getElementById('defaultGamesToggleList');
        if (!list) return;
        if (!defaultGamesCatalog.length) {
            list.innerHTML = '<p class="muted">No predetermined games found.</p>';
            return;
        }

        const prioritized = [...defaultGamesCatalog].sort((a, b) => {
            if (a.id === 'teacher-activities') return -1;
            if (b.id === 'teacher-activities') return 1;
            return a.title.localeCompare(b.title);
        });

        list.innerHTML = prioritized.map(game => `
            <label class="checkbox-item" style="display:flex;align-items:center;gap:.5rem;padding:.5rem;border:1px solid #e5e7eb;border-radius:8px">
                <input type="checkbox" class="default-game-check" data-game-id="${escapeHtml(game.id)}" ${game.is_enabled ? 'checked' : ''} />
                <span style="display:flex;flex-direction:column">
                    <strong style="font-size:.9rem">${escapeHtml(game.title)}</strong>
                    <small class="muted">${escapeHtml(game.subject)} · ${escapeHtml(game.id)}</small>
                </span>
            </label>
        `).join('');
    }

    function setDefaultGameToggles(enabled) {
        document.querySelectorAll('.default-game-check').forEach(cb => {
            cb.checked = enabled;
        });
    }

    function saveDefaultGamesToggles() {
        const checks = [...document.querySelectorAll('.default-game-check')];
        if (checks.length === 0) return;

        const payloadGames = checks.map(cb => ({
            id: cb.dataset.gameId,
            is_enabled: cb.checked
        }));

        const assignAlert = document.getElementById('assignAlert');
        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify({
                action: 'save-default-games',
                games: payloadGames
            })
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                assignAlert.textContent = '✅ Predetermined game toggles saved. Student My Quests will reflect this on refresh.';
                assignAlert.className = 'alert alert-success';
                assignAlert.classList.remove('hidden');
            })
            .catch(err => {
                assignAlert.textContent = `❌ Failed to save toggles: ${err.message}`;
                assignAlert.className = 'alert alert-danger';
                assignAlert.classList.remove('hidden');
            });
    }

    function renderAssignStudents(filterValue) {
        const list = document.getElementById('assignStudentList');
        const match = document.getElementById('assignMatchCount');
        const q = (filterValue || '').trim().toLowerCase();

        const rows = q
            ? assignStudentsCache.filter(s => `${s.first_name} ${s.last_name}`.toLowerCase().includes(q))
            : assignStudentsCache;

        match.textContent = q ? `${rows.length} result${rows.length !== 1 ? 's' : ''}` : '';
        if (rows.length === 0) {
            list.innerHTML = '<p class="muted" style="padding:.35rem 0">No matching students.</p>';
            return;
        }

        list.innerHTML = rows.map(s => `
            <label class="checkbox-item" style="padding:.35rem 0;display:flex;align-items:center;gap:.5rem">
                <input type="checkbox" class="assign-student-check" value="${s.id}" />
                <span>${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)}</span>
            </label>
        `).join('');
    }

    function submitAssignment(studentIds) {
        if (!currentActivityId) {
            showAlert('Please save the activity first.', 'error');
            return;
        }

        const assignAlert = document.getElementById('assignAlert');
        const courseId = document.getElementById('assignCourse').value || null;
        const dueDate = document.getElementById('assignDueDate').value || null;

        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify({
                action: 'assign',
                activity_id: currentActivityId,
                course_id: courseId ? parseInt(courseId, 10) : null,
                student_ids: studentIds,
                due_date: dueDate
            })
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                assignAlert.textContent = studentIds.length > 0
                    ? `✅ Activity assigned to ${studentIds.length} student${studentIds.length > 1 ? 's' : ''}.`
                    : '✅ Activity assigned successfully.';
                assignAlert.className = 'alert alert-success';
                assignAlert.classList.remove('hidden');
            })
            .catch(err => {
                assignAlert.textContent = `❌ ${err.message}`;
                assignAlert.className = 'alert alert-danger';
                assignAlert.classList.remove('hidden');
            });
    }

    function duplicateCurrentActivity() {
        if (!currentActivityId) {
            showAlert('Open an existing activity first to duplicate it.', 'error');
            return;
        }

        fetch(API_BASE, {
            method: 'POST',
            headers: authHeaders(true),
            body: JSON.stringify({ action: 'duplicate', id: currentActivityId })
        })
            .then(res => res.json())
            .then(json => {
                if (!json.success) throw new Error(json.message);
                showAlert('Activity duplicated successfully.', 'success');
                loadActivities();
            })
            .catch(err => {
                showAlert(`Failed to duplicate: ${err.message}`, 'error');
            });
    }

    function loadTeacherInfo() {
        if (window.EQ && window.EQ.teacher) {
            const quickName = window.EQ.teacher.first_name || 'Teacher';
            document.getElementById('teacherName').textContent = quickName;
            document.getElementById('teacherAvatarInitials').textContent = quickName.charAt(0).toUpperCase();
            return;
        }

        fetch('../api/auth/me.php')
            .then(res => res.json())
            .then(json => {
                if (json.success && json.data) {
                    const name = json.data.first_name || 'Teacher';
                    const initials = name.charAt(0).toUpperCase();
                    document.getElementById('teacherName').textContent = name;
                    document.getElementById('teacherAvatarInitials').textContent = initials;
                }
            })
            .catch(err => console.error('Failed to load teacher info:', err));
    }

    function authHeaders(includeContentType) {
        if (window.EQ && typeof window.EQ.authHeaders === 'function') {
            return window.EQ.authHeaders();
        }

        const headers = {};
        if (includeContentType) headers['Content-Type'] = 'application/json';
        return headers;
    }

    function asText(value) {
        return value === null || value === undefined ? '' : String(value);
    }

    function csvToArray(value) {
        return asText(value)
            .split(',')
            .map(part => part.trim())
            .filter(Boolean);
    }

    function arrayToCsv(value) {
        return Array.isArray(value) ? value.join(', ') : '';
    }

    function parseScalarValue(raw) {
        const text = asText(raw).trim();
        if (text === '') return text;
        const num = Number(text);
        return Number.isNaN(num) ? text : num;
    }

    function showAlert(msg, type = 'info') {
        const alert = document.getElementById('builderAlert');
        alert.textContent = msg;
        alert.className = `alert alert-${type}`;
        alert.classList.remove('hidden');
        setTimeout(() => alert.classList.add('hidden'), 5000);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return asText(text).replace(/[&<>"']/g, m => map[m]);
    }

    function handleLogout() {
        fetch('../api/auth/logout.php', {
            method: 'POST',
            credentials: 'include'
        }).catch(() => {}).finally(() => {
            window.location.href = '../../auth/login/login.html?role=teacher';
        });
    }

})();
