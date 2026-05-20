/**
 * learning.js
 * Course Materials page — loads teacher-uploaded course modules and material viewer.
 */
(function () {
    'use strict';

    const API_BASE = '../../EDUQUEST/api/learning';
    const token = localStorage.getItem('eq_token');
    const user  = JSON.parse(localStorage.getItem('eduquest_user') || 'null');

    // Cache for viewer — populated as materials are rendered
    const materialsCache = {};

    if (!token || !user) {
        window.location.href = '../../auth/login/login.html';
        return;
    }

    const authHeaders = () => ({
        'Content-Type':  'application/json',
        'Authorization': 'Bearer ' + token,
    });

    document.addEventListener('DOMContentLoaded', () => {
        loadCourseModules();
        loadNavStats();
    });

    // --- Nav Stats ---
    async function loadNavStats() {
        try {
            const res  = await fetch('../../EDUQUEST/api/gamification/profile.php', { headers: authHeaders() });
            const json = await res.json();
            if (json.success) {
                const p         = json.data.profile;
                const navXp     = document.getElementById('navXp');
                const navStreak = document.getElementById('navStreak');
                const navLevel  = document.getElementById('navLevel');
                if (navXp)     navXp.textContent    = formatNum(p.totalXp) + ' XP';
                if (navStreak) navStreak.textContent = p.streakDays + ' days';
                if (navLevel)  navLevel.textContent  = 'Lv ' + p.level;
            }
        } catch (e) { /* silent */ }
    }

    // --- Teacher Course Modules ---
    async function loadCourseModules() {
        const section   = document.getElementById('courseModulesSection');
        const container = document.getElementById('courseModulesContainer');
        if (!section || !container) return;

        try {
            const res  = await fetch(API_BASE + '/student-modules.php', { headers: authHeaders() });
            const json = await res.json();

            if (!json.success || !json.data || json.data.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-state-icon">\uD83D\uDCDA</div><p>No course materials yet. Your teacher will upload materials here!</p></div>';
                return;
            }

            container.innerHTML = json.data.map(renderCourseCard).join('');

            // Toggle module expand/collapse
            container.querySelectorAll('.cm-module-header').forEach(header => {
                header.addEventListener('click', () => {
                    const body  = header.nextElementSibling;
                    const arrow = header.querySelector('.cm-arrow');
                    body.classList.toggle('cm-collapsed');
                    arrow.classList.toggle('cm-arrow-open');
                    header.setAttribute('aria-expanded', !body.classList.contains('cm-collapsed'));
                });
            });

        } catch (e) { /* silent */ }
    }

    function renderCourseCard(course) {
        const color         = course.coverColor || '#8b5cf6';
        const moduleCount   = course.modules.length;
        const materialCount = course.modules.reduce((sum, m) => sum + m.materials.length, 0);
        const announcements = Array.isArray(course.announcements) ? course.announcements : [];

        let modulesHtml = '';
        let modIdx = 0;
        course.modules.forEach(mod => {
            const isFirst = modIdx === 0;
            const matHtml = mod.materials.map(mat => renderMaterialItem(mat)).join('');
            const matCount = mod.materials.length;
            modulesHtml += `
            <div class="cm-module">
                <div class="cm-module-header" aria-expanded="${isFirst}">
                    <span class="cm-arrow${isFirst ? ' cm-arrow-open' : ''}">&#9658;</span>
                    <span class="cm-module-title">${escapeHtml(mod.title)}</span>
                    <span class="cm-material-count">${matCount} item${matCount !== 1 ? 's' : ''}</span>
                </div>
                <div class="cm-module-body${isFirst ? '' : ' cm-collapsed'}">
                    ${matHtml || '<p class="cm-empty">No materials in this module yet.</p>'}
                </div>
            </div>`;
            modIdx++;
        });

        return `
        <div class="cm-course-card">
            <div class="cm-course-header" style="border-left: 5px solid ${escapeHtml(color)}">
                <div class="cm-course-info">
                    <h3 class="cm-course-title">${escapeHtml(course.title)}</h3>
                    <p class="cm-course-meta">
                        \uD83D\uDC69&#x200D;\uD83C\uDFEB ${escapeHtml(course.teacherName)}
                        ${course.subject ? ' &bull; \uD83D\uDCD8 ' + escapeHtml(course.subject) : ''}
                    </p>
                </div>
                <div class="cm-course-stats">
                    <span class="cm-stat">\uD83D\uDCE6 ${moduleCount} module${moduleCount !== 1 ? 's' : ''}</span>
                    <span class="cm-stat">\uD83D\uDCC4 ${materialCount} file${materialCount !== 1 ? 's' : ''}</span>
                </div>
            </div>
            <div class="cm-modules-list">
                ${renderAnnouncementBlock(announcements)}
                ${modulesHtml || '<p class="cm-empty">No modules in this course yet.</p>'}
            </div>
        </div>`;
    }

    function renderAnnouncementBlock(announcements) {
        if (!announcements.length) {
            return '<div class="cm-announcements cm-announcements-empty"><span class="cm-announcements-title">📣 Announcements</span><p>No announcements yet.</p></div>';
        }

        const items = announcements.map(a => {
            const when = a.createdAt ? new Date(a.createdAt).toLocaleDateString() : '';
            return `
                <article class="cm-ann-item${a.isPinned ? ' is-pinned' : ''}">
                    <div class="cm-ann-title-row">
                        <h4 class="cm-ann-title">${escapeHtml(a.title || 'Announcement')}</h4>
                        <span class="cm-ann-date">${escapeHtml(when)}</span>
                    </div>
                    <p class="cm-ann-content">${escapeHtml(a.content || '')}</p>
                </article>`;
        }).join('');

        return `
            <section class="cm-announcements">
                <div class="cm-announcements-head">
                    <span class="cm-announcements-title">📣 Announcements</span>
                </div>
                <div class="cm-announcements-list">${items}</div>
            </section>`;
    }

    // Icon lookup — used in renderMaterialItem and buildViewerContent
    const ICONS = {
        pdf: '\uD83D\uDCD5', word: '\uD83D\uDCD8', ppt: '\uD83D\uDCD9', spreadsheet: '\uD83D\uDCD7',
        image: '\uD83D\uDDBB', video: '\uD83C\uDFAC', audio: '\uD83C\uDFB5',
        link: '\uD83D\uDD17', text: '\uD83D\uDCDD', assignment: '\uD83D\uDCCB', file: '\uD83D\uDCCE'
    };

    function renderMaterialItem(mat) {
        materialsCache[mat.id] = mat;
        const icon = ICONS[mat.fileType || mat.materialType] || '\uD83D\uDCCE';

        if (mat.materialType === 'file') {
            const downloadUrl = API_BASE + '/student-material-download.php?id=' + mat.id;
            const sizeStr     = mat.fileSize ? formatFileSize(mat.fileSize) : '';
            return `
            <div class="cm-material-item">
                <span class="cm-mat-icon">${icon}</span>
                <div class="cm-mat-info">
                    <span class="cm-mat-title cm-mat-clickable" onclick="openMaterialViewer(${mat.id})">${escapeHtml(mat.title || mat.fileName)}</span>
                    <span class="cm-mat-detail">${escapeHtml(mat.fileName || '')}${sizeStr ? ' &bull; ' + sizeStr : ''}</span>
                </div>
                <div class="cm-mat-actions">
                    <button type="button" class="cm-btn cm-btn-preview" data-track="View Material" onclick="openMaterialViewer(${mat.id})">\uD83D\uDC41 View</button>
                    <a href="${downloadUrl}" class="cm-btn cm-btn-download" title="Download">\u2B07 Download</a>
                </div>
            </div>`;
        }

        if (mat.materialType === 'link') {
            return `
            <div class="cm-material-item">
                <span class="cm-mat-icon">${icon}</span>
                <div class="cm-mat-info">
                    <span class="cm-mat-title">${escapeHtml(mat.title)}</span>
                    ${mat.description ? `<span class="cm-mat-detail">${escapeHtml(mat.description)}</span>` : ''}
                </div>
                <div class="cm-mat-actions">
                    <button type="button" class="cm-btn cm-btn-preview" data-track="View Material" onclick="openMaterialViewer(${mat.id})">\uD83D\uDC41 View</button>
                    <a href="${escapeHtml(mat.url)}" target="_blank" rel="noopener noreferrer" class="cm-btn cm-btn-download">\uD83D\uDD17 Open</a>
                </div>
            </div>`;
        }

        if (mat.materialType === 'text') {
            return `
            <div class="cm-material-item cm-material-text">
                <span class="cm-mat-icon">${icon}</span>
                <div class="cm-mat-info">
                    <span class="cm-mat-title cm-mat-clickable" onclick="openMaterialViewer(${mat.id})">${escapeHtml(mat.title)}</span>
                    <div class="cm-text-content">${escapeHtml(mat.content || '').substring(0, 120)}${(mat.content || '').length > 120 ? '\u2026' : ''}</div>
                </div>
                <div class="cm-mat-actions">
                    <button type="button" class="cm-btn cm-btn-preview" data-track="Read Material" onclick="openMaterialViewer(${mat.id})">\uD83D\uDCD6 Read</button>
                </div>
            </div>`;
        }

        if (mat.materialType === 'assignment') {
            const sub = mat.submission;
            let statusHtml = '';
            let uploadHtml = '';

            if (sub) {
                const statusClass = sub.status === 'graded'   ? 'cm-status-graded'
                                  : sub.status === 'returned' ? 'cm-status-returned'
                                  : 'cm-status-submitted';
                const statusLabel = sub.status === 'graded'   ? '\u2705 Graded'
                                  : sub.status === 'returned' ? '\uD83D\uDD04 Returned'
                                  : '\uD83D\uDCE4 Submitted';
                statusHtml = `
                    <div class="cm-submission-status ${statusClass}">
                        <span class="cm-sub-badge">${statusLabel}</span>
                        <span class="cm-sub-file">\uD83D\uDCCE ${escapeHtml(sub.originalFilename)} &bull; ${formatFileSize(sub.fileSize)}</span>
                        <span class="cm-sub-date">Submitted ${new Date(sub.submittedAt).toLocaleDateString()}</span>
                        ${sub.grade !== null && sub.grade !== undefined ? `<span class="cm-sub-grade">Grade: <strong>${sub.grade}</strong></span>` : ''}
                        ${sub.feedback ? `<div class="cm-sub-feedback"><strong>Feedback:</strong> ${escapeHtml(sub.feedback)}</div>` : ''}
                    </div>`;
                uploadHtml = `<div class="cm-resubmit"><button class="cm-btn cm-btn-resubmit" onclick="toggleSubmitForm(${mat.id})">\uD83D\uDD04 Resubmit</button></div>`;
            } else {
                uploadHtml = `<div class="cm-upload-prompt"><button class="cm-btn cm-btn-upload" onclick="toggleSubmitForm(${mat.id})">\uD83D\uDCE4 Submit Assignment</button></div>`;
            }

            return `
            <div class="cm-material-item cm-assignment-item" id="assignment-${mat.id}">
                <span class="cm-mat-icon">${icon}</span>
                <div class="cm-mat-info cm-assignment-info">
                    <span class="cm-mat-title">${escapeHtml(mat.title)}</span>
                    ${mat.dueDate ? `<span class="cm-mat-detail">\uD83D\uDCC5 Due: ${new Date(mat.dueDate).toLocaleDateString()}</span>` : ''}
                    ${mat.content ? `<div class="cm-text-content">${escapeHtml(mat.content)}</div>` : ''}
                    ${statusHtml}
                    ${uploadHtml}
                    <div class="cm-submit-form" id="submitForm-${mat.id}" style="display:none;">
                        <form onsubmit="submitAssignment(event, ${mat.id})" enctype="multipart/form-data">
                            <div class="cm-upload-zone" id="dropZone-${mat.id}">
                                <input type="file" id="fileInput-${mat.id}" class="cm-file-input"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip,.rar" />
                                <div class="cm-upload-zone-inner">
                                    <span>\uD83D\uDCC1</span>
                                    <p>Click to select file or drag &amp; drop</p>
                                    <p class="cm-upload-hint">PDF, Word, Excel, PPT, Images &mdash; max 10 MB</p>
                                </div>
                            </div>
                            <div class="cm-selected-file" id="selectedFile-${mat.id}" style="display:none;">
                                <span id="selectedFileName-${mat.id}"></span>
                                <button type="button" class="cm-btn-clear" onclick="clearSelectedFile(${mat.id})">&times;</button>
                            </div>
                            <textarea id="notes-${mat.id}" class="cm-notes-input" placeholder="Add a note (optional)..." rows="2"></textarea>
                            <div class="cm-submit-actions">
                                <button type="submit" class="cm-btn cm-btn-submit" id="submitBtn-${mat.id}">\uD83D\uDCE4 Upload &amp; Submit</button>
                                <button type="button" class="cm-btn cm-btn-cancel" onclick="toggleSubmitForm(${mat.id})">Cancel</button>
                            </div>
                            <div class="cm-upload-progress" id="progress-${mat.id}" style="display:none;">
                                <div class="cm-progress-bar"><div class="cm-progress-fill" id="progressFill-${mat.id}"></div></div>
                                <span class="cm-progress-text" id="progressText-${mat.id}">Uploading...</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>`;
        }

        return '';
    }

    // --- Assignment submission handlers ---

    window.toggleSubmitForm = function(materialId) {
        const form = document.getElementById('submitForm-' + materialId);
        if (form) form.style.display = form.style.display === 'none' ? '' : 'none';
    };

    window.clearSelectedFile = function(materialId) {
        const input = document.getElementById('fileInput-' + materialId);
        if (input) input.value = '';
        const sel = document.getElementById('selectedFile-' + materialId);
        if (sel) sel.style.display = 'none';
    };

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('cm-file-input')) {
            const id      = e.target.id.replace('fileInput-', '');
            const file    = e.target.files[0];
            const selDiv  = document.getElementById('selectedFile-' + id);
            const selName = document.getElementById('selectedFileName-' + id);
            if (file && selDiv && selName) {
                selName.textContent  = file.name + ' (' + formatFileSize(file.size) + ')';
                selDiv.style.display = '';
            }
        }
    });

    window.submitAssignment = async function(e, materialId) {
        e.preventDefault();

        const fileInput    = document.getElementById('fileInput-'    + materialId);
        const notesInput   = document.getElementById('notes-'        + materialId);
        const submitBtn    = document.getElementById('submitBtn-'    + materialId);
        const progressDiv  = document.getElementById('progress-'     + materialId);
        const progressFill = document.getElementById('progressFill-' + materialId);
        const progressText = document.getElementById('progressText-' + materialId);

        if (!fileInput || !fileInput.files[0]) { alert('Please select a file to submit.'); return; }

        const file = fileInput.files[0];
        if (file.size > 10 * 1024 * 1024) { alert('File exceeds the 10 MB limit.'); return; }

        const formData = new FormData();
        formData.append('materialId', materialId);
        formData.append('file', file);
        formData.append('notes', notesInput ? notesInput.value : '');

        if (submitBtn)   submitBtn.disabled     = true;
        if (progressDiv) progressDiv.style.display = '';

        try {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', API_BASE + '/submit-assignment.php');
            xhr.setRequestHeader('Authorization', 'Bearer ' + token);

            xhr.upload.onprogress = function(ev) {
                if (ev.lengthComputable && progressFill && progressText) {
                    const pct = Math.round((ev.loaded / ev.total) * 100);
                    progressFill.style.width  = pct + '%';
                    progressText.textContent   = pct + '% uploaded...';
                }
            };

            xhr.onload = function() {
                let json;
                try { json = JSON.parse(xhr.responseText); }
                catch (_) { json = { success: false, message: 'Invalid server response.' }; }

                if (json.success) {
                    loadCourseModules();
                } else {
                    alert(json.message || 'Submission failed.');
                    if (submitBtn)   submitBtn.disabled     = false;
                    if (progressDiv) progressDiv.style.display = 'none';
                }
            };

            xhr.onerror = function() {
                alert('Network error. Please try again.');
                if (submitBtn)   submitBtn.disabled     = false;
                if (progressDiv) progressDiv.style.display = 'none';
            };

            xhr.send(formData);
        } catch (err) {
            alert('Upload failed. Please try again.');
            if (submitBtn)   submitBtn.disabled     = false;
            if (progressDiv) progressDiv.style.display = 'none';
        }
    };

    // --- Material Viewer ---

    window.openMaterialViewer = function(matId) {
        const mat = materialsCache[matId];
        if (!mat) return;

        const panel     = document.getElementById('lvPanel');
        const icon       = document.getElementById('lvIcon');
        const title      = document.getElementById('lvTitle');
        const meta       = document.getElementById('lvMeta');
        const body       = document.getElementById('lvBody');
        const dlBtn      = document.getElementById('lvDownloadBtn');
        const tabBtn     = document.getElementById('lvOpenTabBtn');
        if (!panel) return;

        icon.textContent  = ICONS[mat.fileType || mat.materialType] || '\uD83D\uDCCE';
        title.textContent = mat.title || mat.fileName || 'Material';

        const metaParts = [];
        if (mat.fileName) metaParts.push(mat.fileName);
        if (mat.fileSize) metaParts.push(formatFileSize(mat.fileSize));
        if (mat.materialType === 'link' && mat.url)
            metaParts.push(mat.url.replace(/^https?:\/\//, '').split('/')[0]);
        meta.textContent = metaParts.join(' \u2022 ');

        if (mat.materialType === 'file') {
            dlBtn.href          = API_BASE + '/student-material-download.php?id=' + mat.id;
            dlBtn.style.display = '';
            tabBtn.href         = API_BASE + '/student-material-preview.php?id=' + mat.id;
            tabBtn.style.display = '';
        } else if (mat.materialType === 'link' && mat.url) {
            dlBtn.style.display  = 'none';
            tabBtn.href          = mat.url;
            tabBtn.style.display = '';
        } else {
            dlBtn.style.display  = 'none';
            tabBtn.style.display = 'none';
        }

        body.innerHTML = buildViewerContent(mat);

        // Reset animation so it replays on each open
        panel.classList.remove('lv-open');
        panel.style.display = 'block';
        const lvSign = document.getElementById('lvPanelSign');
        if (lvSign) lvSign.style.display = '';
        void panel.offsetHeight; // force reflow
        panel.classList.add('lv-open');
        // Scroll after paint so the panel has layout
        requestAnimationFrame(function() {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    };

    function buildViewerContent(mat) {
        const previewUrl  = API_BASE + '/student-material-preview.php?id='  + mat.id;
        const downloadUrl = API_BASE + '/student-material-download.php?id=' + mat.id;

        if (mat.materialType === 'text') {
            return `<div class="lv-text-body">${escapeHtml(mat.content || '').replace(/\n/g, '<br>')}</div>`;
        }

        if (mat.materialType === 'link') {
            return `<div class="lv-link-body">
                <div class="lv-link-card">
                    <div class="lv-link-icon">\uD83D\uDD17</div>
                    <h4>${escapeHtml(mat.title)}</h4>
                    ${mat.description ? `<p class="lv-link-desc">${escapeHtml(mat.description)}</p>` : ''}
                    <p class="lv-link-url">${escapeHtml(mat.url || '')}</p>
                    <a href="${escapeHtml(mat.url || '#')}" target="_blank" rel="noopener noreferrer" class="lv-open-link-btn">Open Link &#x2197;</a>
                </div>
            </div>`;
        }

        if (mat.materialType === 'assignment') {
            const sub = mat.submission;
            let statusHtml = '';
            if (sub) {
                const labels = { graded: '\u2705 Graded', returned: '\uD83D\uDD04 Returned' };
                const label  = labels[sub.status] || '\uD83D\uDCE4 Submitted';
                statusHtml = `<div class="lv-sub-status">
                    <span class="lv-sub-badge ${sub.status}">${label}</span>
                    <span>\uD83D\uDCCE ${escapeHtml(sub.originalFilename)}</span>
                    ${sub.grade !== null && sub.grade !== undefined ? `<span>Grade: <strong>${sub.grade}</strong></span>` : ''}
                    ${sub.feedback ? `<div class="lv-sub-feedback"><strong>Feedback:</strong> ${escapeHtml(sub.feedback)}</div>` : ''}
                </div>`;
            }
            return `<div class="lv-assignment-body">
                ${mat.dueDate ? `<div class="lv-due-date">\uD83D\uDCC5 Due: <strong>${new Date(mat.dueDate).toLocaleDateString()}</strong></div>` : ''}
                ${mat.content ? `<div class="lv-assignment-instructions">${escapeHtml(mat.content).replace(/\n/g, '<br>')}</div>` : ''}
                ${statusHtml}
                <div class="lv-submit-section" id="lvSubmitSection-${mat.id}">
                    <button type="button" class="cm-btn cm-btn-upload"
                        onclick="document.getElementById('lvSubmitForm-${mat.id}').style.display=''">
                        \uD83D\uDCE4 ${sub ? 'Resubmit' : 'Submit Assignment'}
                    </button>
                    <div id="lvSubmitForm-${mat.id}" style="display:none;" class="lv-submit-form">
                        <form onsubmit="submitAssignment(event, ${mat.id})" enctype="multipart/form-data">
                            <div class="cm-upload-zone" id="dropZone-${mat.id}">
                                <input type="file" id="fileInput-${mat.id}" class="cm-file-input"
                                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.txt,.csv,.zip,.rar" />
                                <div class="cm-upload-zone-inner">
                                    <span>\uD83D\uDCC1</span>
                                    <p>Click to select file or drag &amp; drop</p>
                                    <p class="cm-upload-hint">PDF, Word, Excel, PPT, Images &mdash; max 10 MB</p>
                                </div>
                            </div>
                            <div class="cm-selected-file" id="selectedFile-${mat.id}" style="display:none;">
                                <span id="selectedFileName-${mat.id}"></span>
                                <button type="button" class="cm-btn-clear" onclick="clearSelectedFile(${mat.id})">&times;</button>
                            </div>
                            <textarea id="notes-${mat.id}" class="cm-notes-input" placeholder="Add a note (optional)..." rows="2"></textarea>
                            <div class="cm-submit-actions">
                                <button type="submit" class="cm-btn cm-btn-submit" id="submitBtn-${mat.id}">\uD83D\uDCE4 Upload &amp; Submit</button>
                                <button type="button" class="cm-btn cm-btn-cancel"
                                    onclick="document.getElementById('lvSubmitForm-${mat.id}').style.display='none'">Cancel</button>
                            </div>
                            <div class="cm-upload-progress" id="progress-${mat.id}" style="display:none;">
                                <div class="cm-progress-bar"><div class="cm-progress-fill" id="progressFill-${mat.id}"></div></div>
                                <span class="cm-progress-text" id="progressText-${mat.id}">Uploading...</span>
                            </div>
                        </form>
                    </div>
                </div>
            </div>`;
        }

        if (mat.materialType === 'file') {
            if (mat.fileType === 'pdf') {
                return `<iframe class="lv-iframe" src="${previewUrl}" title="${escapeHtml(mat.title || mat.fileName)}"></iframe>`;
            }
            if (mat.fileType === 'image') {
                return `<div class="lv-img-wrap"><img class="lv-image" src="${previewUrl}" alt="${escapeHtml(mat.title || mat.fileName)}"></div>`;
            }
            if (mat.fileType === 'video') {
                return `<div class="lv-video-wrap"><video class="lv-video" src="${previewUrl}" controls controlsList="nodownload"></video></div>`;
            }
            if (mat.fileType === 'audio') {
                return `<div class="lv-audio-wrap"><audio class="lv-audio" src="${previewUrl}" controls></audio></div>`;
            }
            // Office / unsupported
            return `<div class="lv-unsupported">
                <div class="lv-unsupported-icon">${ICONS[mat.fileType] || '\uD83D\uDCCE'}</div>
                <h4>${escapeHtml(mat.fileName || mat.title || 'File')}</h4>
                <p>This file type cannot be previewed in the browser.</p>
                <a href="${downloadUrl}" class="lv-download-big">\u2B07 Download to Open</a>
            </div>`;
        }

        return '<div class="lv-unsupported"><p>Unable to display this material.</p></div>';
    }

    function closeViewer() {
        const panel = document.getElementById('lvPanel');
        if (!panel) return;
        panel.style.display = 'none';
        panel.classList.remove('lv-open');
        const lvSign = document.getElementById('lvPanelSign');
        if (lvSign) lvSign.style.display = 'none';
        panel.querySelectorAll('video, audio').forEach(el => el.pause());
    }

    document.addEventListener('DOMContentLoaded', function() {
        const closeBtn = document.getElementById('lvClose');
        if (closeBtn) closeBtn.addEventListener('click', closeViewer);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeViewer();
        });
    });

    // --- Utilities ---

    function formatFileSize(bytes) {
        if (!bytes) return '';
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function formatNum(n) {
        n = parseInt(n) || 0;
        if (n >= 1000) return (n / 1000).toFixed(1) + 'K';
        return n.toLocaleString();
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

})();