@extends('layouts.admin')

@section('title', 'Chats Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-comment-dots me-2"></i>Chats Management</h4>
            {{-- <small class="text-muted" id="recordCount">Loading...</small> --}}
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       id="searchChats"
                       placeholder="Search by chat ID or participant..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-6 col-lg-8 text-md-end">
            <span class="text-muted small" id="recordCount">Loading...</span>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">

            <!-- SKELETON LOADING -->
            <div id="chats-skeleton">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="border-bottom-2">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Chat</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Participants</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Type</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Last Message</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px;">Updated</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for($i = 0; $i < 6; $i++)
                                <tr>
                                    <td class="text-center">
                                        <div class="skeleton-box mx-auto" style="width: 20px; height: 16px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="skeleton-box mb-1" style="width: 120px; height: 16px; border-radius: 4px;"></div>
                                            <div class="skeleton-box" style="width: 80px; height: 12px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="skeleton-box me-1" style="width: 32px; height: 32px; border-radius: 50%;"></div>
                                            <div class="skeleton-box me-1" style="width: 32px; height: 32px; border-radius: 50%;"></div>
                                            <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 50%;"></div>
                                            <div class="skeleton-box ms-2" style="width: 40px; height: 12px; border-radius: 4px;"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box mx-auto" style="width: 60px; height: 24px; border-radius: 20px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 120px; height: 14px; border-radius: 4px;"></div>
                                    </td>
                                    <td>
                                        <div class="skeleton-box" style="width: 80px; height: 12px; border-radius: 4px;"></div>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <div class="skeleton-box" style="width: 32px; height: 32px; border-radius: 8px;"></div>
                                        </div>
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ACTUAL CONTENT -->
            <div id="chats-content" style="display: none; animation: fadeIn 0.5s ease-in-out;">
                <div class="table-responsive" id="chatsTableWrapper" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                            <tr>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Chat</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 160px;">Participants</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Type</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Last Message</th>
                                <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Updated</th>
                                <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="chatsTableBody">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>

                {{-- Scroll indicator --}}
                <div id="scrollIndicator" class="text-center text-muted small mt-2" style="display: none;">
                    <i class="fas fa-chevron-down me-1"></i> Scroll for more records
                </div>
            </div>
        </div>
    </div>
</div>

{{-- View Chat Modal --}}
<div class="modal fade" id="viewChatModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="viewChatTitle">
                    <i class="fas fa-comment-dots me-2"></i>Chat Details
                </h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewChatContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Skeleton Loading Animation */
    @keyframes skeleton-pulse {
        0% { opacity: 0.6; }
        50% { opacity: 1; }
        100% { opacity: 0.6; }
    }

    .skeleton-box {
        display: block;
        background-color: #e9ecef;
        border-radius: 4px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .action-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #6c757d;
        transition: all 0.2s ease;
    }
    .action-btn:hover {
        background: #f1f1f1;
        color: #000;
    }
    .table > :not(caption) > * > * {
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
    }
    .chat-type-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .chat-type-badge.group {
        background: #dbeafe;
        color: #1e40af;
    }
    .chat-type-badge.direct {
        background: #d1fae5;
        color: #065f46;
    }
    .participant-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.7rem;
        color: white;
        margin-right: -8px;
        border: 2px solid white;
        flex-shrink: 0;
    }
    .participant-avatar:last-child {
        margin-right: 0;
    }
    .participant-name {
        font-size: 0.8rem;
        font-weight: 500;
    }
    .participant-email {
        font-size: 0.7rem;
        color: #6c757d;
    }
    .message-bubble {
        padding: 8px 14px;
        border-radius: 12px;
        max-width: 75%;
        word-wrap: break-word;
    }
    .message-bubble.sent {
        background: #1a1a1a;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .message-bubble.received {
        background: #f1f1f1;
        color: #1a1a1a;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    .message-time {
        font-size: 0.6rem;
        opacity: 0.6;
        margin-top: 4px;
    }
    .messages-container {
        max-height: 350px;
        overflow-y: auto;
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .messages-container::-webkit-scrollbar {
        width: 4px;
    }
    .messages-container::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 4px;
    }

    #chatsTableWrapper::-webkit-scrollbar {
        width: 6px;
    }
    #chatsTableWrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #chatsTableWrapper::-webkit-scrollbar-thumb {
        background: #d1d1d1;
        border-radius: 10px;
    }
    #chatsTableWrapper::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .input-group .form-control:focus {
        border-color: #dee2e6;
        box-shadow: none;
    }

    .input-group .form-control:focus + .input-group-text {
        border-color: #dee2e6;
    }
</style>
@endsection

@push('scripts')
<script>
let currentChatId = null;
let allChats = [];
let filteredChats = [];

// Get CSRF token from meta tag
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// Load chats on page load
document.addEventListener('DOMContentLoaded', function() {
    loadChats();
    setupSearch();
});

// Setup search functionality
function setupSearch() {
    const searchInput = document.getElementById('searchChats');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            filterChats(query);
        });
    }
}

// Filter chats based on search query
function filterChats(query) {
    if (!query) {
        filteredChats = allChats;
    } else {
        filteredChats = allChats.filter(chat => {
            // Search by chat ID
            const chatId = (chat.chatid || '').toLowerCase();
            // Search by participant names
            let participantNames = '';
            if (chat.userDetails) {
                participantNames = chat.userDetails.map(u => (u.username || '').toLowerCase()).join(' ');
            }
            return chatId.includes(query) || participantNames.includes(query);
        });
    }
    renderChats(filteredChats);
    updateFilteredCount(filteredChats.length);
}

// Update filtered count display
function updateFilteredCount(count) {
    const countElement = document.getElementById('filteredCount');
    const recordCount = document.getElementById('recordCount');
    if (countElement) {
        if (count === 1) {
            countElement.textContent = '1 record found';
        } else {
            countElement.textContent = count + ' records found';
        }
    }
    if (recordCount) {
        recordCount.textContent = `(${allChats.length} total records)`;
    }
}

// Load all chats
async function loadChats() {
    const skeleton = document.getElementById('chats-skeleton');
    const content = document.getElementById('chats-content');
    const recordCount = document.getElementById('recordCount');

    try {
        const response = await fetch('/admin/api/chats');
        const data = await response.json();

        if (data.success) {
            allChats = data.data || [];
            filteredChats = allChats;
            renderChats(filteredChats);
            updateFilteredCount(filteredChats.length);
            recordCount.textContent = `(${allChats.length} total records)`;
        } else {
            showToast('Failed to load chats', 'danger');
            renderChats([]);
            updateFilteredCount(0);
            recordCount.textContent = '(0 records)';
        }
    } catch (error) {
        console.error('Error loading chats:', error);
        showToast('Error loading chats', 'danger');
        renderChats([]);
        updateFilteredCount(0);
        recordCount.textContent = '(0 records)';
    } finally {
        // Hide skeleton, show content
        if (skeleton) skeleton.style.display = 'none';
        if (content) content.style.display = 'block';
    }
}

// Render chats in table
function renderChats(chats) {
    const tbody = document.getElementById('chatsTableBody');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('chatsTableWrapper');

    // Show/hide scroll indicator based on record count
    if (chats && chats.length > 10) {
        if (tableWrapper) {
            tableWrapper.style.maxHeight = '600px';
            tableWrapper.style.overflowY = 'auto';
        }
        if (scrollIndicator) scrollIndicator.style.display = 'block';
    } else {
        if (tableWrapper) {
            tableWrapper.style.maxHeight = 'none';
            tableWrapper.style.overflowY = 'visible';
        }
        if (scrollIndicator) scrollIndicator.style.display = 'none';
    }

    if (!chats || chats.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-comment-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">${allChats.length > 0 ? 'No matching chats found' : 'No chats found'}</p>
                        <small>${allChats.length > 0 ? 'Try a different search term' : 'Chats will appear here when users start conversations'}</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    chats.forEach((chat, index) => {
        const typeClass = chat.isGroup ? 'group' : 'direct';
        const typeLabel = chat.isGroup ? 'Group' : 'Direct';
        const updatedDate = chat.updatedAt ? new Date(chat.updatedAt).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }) : 'N/A';

        // Participants avatars
        let avatarsHtml = '';
        const participants = chat.userDetails || [];
        const displayParticipants = participants.slice(0, 3);
        const extraCount = participants.length - 3;

        const colors = ['#2563eb', '#dc2626', '#059669', '#d97706', '#7c3aed', '#0891b2', '#e11d48', '#0d9488'];

        displayParticipants.forEach((user, idx) => {
            const initial = (user.username || 'U').substring(0, 1).toUpperCase();
            const color = colors[idx % colors.length];
            avatarsHtml += `
                <div class="participant-avatar" style="background: ${color};" title="${escapeHtml(user.username)}">
                    ${initial}
                </div>
            `;
        });

        if (extraCount > 0) {
            avatarsHtml += `
                <div class="participant-avatar" style="background: #6b7280; font-size: 0.6rem;">
                    +${extraCount}
                </div>
            `;
        }

        // Chat name
        let chatName = '';
        if (chat.isGroup) {
            chatName = 'Group Chat';
        } else if (participants.length > 0) {
            chatName = participants[0]?.username || 'Unknown User';
            if (participants.length > 1) {
                chatName += ` & ${participants[1]?.username || 'Unknown'}`;
            }
        } else {
            chatName = 'Chat';
        }

        // Decode base64 message if needed
        let lastMessage = chat.lastMessage || 'No messages yet';
        if (lastMessage && lastMessage.length > 10 && /^[A-Za-z0-9+/=]+$/.test(lastMessage)) {
            try {
                lastMessage = atob(lastMessage);
            } catch (e) {
                // If decoding fails, keep original
            }
        }

        html += `
            <tr>
                <td class="text-center text-muted">${index + 1}</td>
                <td>
                    <div>
                        <div class="fw-bold text-dark">${escapeHtml(chatName)}</div>
                        <div class="text-muted small">${escapeHtml(chat.chatid)}</div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        ${avatarsHtml}
                        <span class="ms-2 small text-muted">${participants.length} participant${participants.length !== 1 ? 's' : ''}</span>
                    </div>
                </td>
                <td><span class="chat-type-badge ${typeClass}">${typeLabel}</span></td>
                <td>
                    <div style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        ${escapeHtml(lastMessage)}
                    </div>
                </td>
                <td class="text-muted small">${updatedDate}</td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewChat('${chat.id}')" title="View Chat">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

// View Chat
async function viewChat(chatId) {
    currentChatId = chatId;
    document.getElementById('viewChatTitle').textContent = '💬 Chat Details';

    document.getElementById('viewChatContent').innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('viewChatModal'));
    modal.show();

    try {
        const response = await fetch(`/admin/api/chats/${chatId}`);
        const data = await response.json();

        if (data.success) {
            renderChatDetails(data.data);
        } else {
            document.getElementById('viewChatContent').innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>Failed to load chat details.</p>
                </div>
            `;
            showToast('Failed to load chat details', 'danger');
        }
    } catch (error) {
        console.error('Error loading chat:', error);
        document.getElementById('viewChatContent').innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p>Error loading chat details.</p>
            </div>
        `;
        showToast('Error loading chat details', 'danger');
    }
}

// Render chat details
function renderChatDetails(chat) {
    const content = document.getElementById('viewChatContent');
    const participants = chat.userDetails || [];
    const messages = chat.messages || [];

    // Participants list
    let participantsHtml = participants.map((user, idx) => {
        const colors = ['#2563eb', '#dc2626', '#059669', '#d97706', '#7c3aed', '#0891b2'];
        const color = colors[idx % colors.length];
        const initial = (user.username || 'U').substring(0, 1).toUpperCase();
        return `
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="participant-avatar" style="background: ${color}; width: 36px; height: 36px; font-size: 0.8rem;">
                    ${initial}
                </div>
                <div>
                    <div class="participant-name">${escapeHtml(user.username)}</div>
                    <div class="participant-email">${escapeHtml(user.email)}</div>
                </div>
            </div>
        `;
    }).join('');

    // Messages
    let messagesHtml = '';
    if (messages.length === 0) {
        messagesHtml = `
            <div class="text-center text-muted py-4">
                <i class="fas fa-comment fa-2x d-block mb-2 opacity-50"></i>
                <small>No messages in this chat yet</small>
            </div>
        `;
    } else {
        messagesHtml = messages.map(msg => {
            // Decode base64 message if needed
            let messageText = msg.message;
            if (messageText && messageText.length > 10 && /^[A-Za-z0-9+/=]+$/.test(messageText)) {
                try {
                    messageText = atob(messageText);
                } catch (e) {
                    // Keep original if decoding fails
                }
            }

            const isCurrentUser = msg.senderid === sessionStorage.getItem('firebase_user_id');
            const messageClass = isCurrentUser ? 'sent' : 'received';
            const time = msg.createdAt ? new Date(msg.createdAt).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';

            return `
                <div class="message-bubble ${messageClass}">
                    <div class="small fw-bold">${escapeHtml(msg.senderName || 'Unknown')}</div>
                    <div>${escapeHtml(messageText)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
        }).join('');
    }

    content.innerHTML = `
        <div class="mb-3">
            <div class="row">
                <div class="col-6">
                    <small class="text-muted">Chat ID</small>
                    <p class="fw-bold small">${escapeHtml(chat.chatid)}</p>
                </div>
                <div class="col-6">
                    <small class="text-muted">Type</small>
                    <p><span class="chat-type-badge ${chat.isGroup ? 'group' : 'direct'}">${chat.isGroup ? 'Group' : 'Direct'}</span></p>
                </div>
                <div class="col-12">
                    <small class="text-muted">Participants (${participants.length})</small>
                    <div class="mt-1">${participantsHtml}</div>
                </div>
            </div>
        </div>
        <hr>
        <div class="messages-container">
            ${messagesHtml}
        </div>
    `;
}

// Toast notification
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast show align-items-center text-white bg-${type === 'danger' ? 'danger' : 'dark'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;

    document.getElementById('toastContainer').insertAdjacentHTML('beforeend', toastHtml);

    setTimeout(() => {
        const toast = document.getElementById(toastId);
        if (toast) toast.remove();
    }, 5000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
@endpush
