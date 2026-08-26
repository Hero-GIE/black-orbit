@extends('layouts.admin')

@section('title', 'Players Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-user-astronaut me-2"></i>Players Management</h4>
            <small class="text-muted" id="recordCount">Loading...</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive" id="playersTableWrapper" style="max-height: 900px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0">
                    <thead class="border-bottom-2 sticky-top bg-white" style="top: 0; z-index: 10;">
                        <tr>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 50px;">#</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 180px;">Player</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 100px;">Destination</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 80px;">XP</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Phase</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 150px;">Crew</th>
                            <th class="text-muted text-uppercase fs-6" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Resources</th>
                            <th class="text-muted text-uppercase fs-6 text-end" style="font-size: 0.7rem; letter-spacing: 0.5px; min-width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="playersTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <div class="spinner-border text-dark mb-3" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mb-0">Loading players...</p>
                                </div>
                            </td>
                        </tr>
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

{{-- View Player Modal --}}
<div class="modal fade" id="viewPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-astronaut me-2 text-dark"></i>Player Details</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="viewPlayerContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-dark" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-dark px-4" id="editFromViewBtn">
                    <i class="fas fa-edit me-1"></i> Edit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Player Modal --}}
<div class="modal fade" id="editPlayerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Player</h5>
                <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <form id="editPlayerForm">
                    <input type="hidden" id="editPlayerId" name="playerId">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label text-muted small fw-bold">Username *</label>
                        <input type="text" class="form-control form-control-custom" id="editUsername" name="username" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editDestination" class="form-label text-muted small fw-bold">Destination</label>
                            <select class="form-control form-control-custom" id="editDestination" name="destination">
                                <option value="moon">Moon</option>
                                <option value="mars">Mars</option>
                                <option value="venus">Venus</option>
                                <option value="jupiter">Jupiter</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editPhase" class="form-label text-muted small fw-bold">Phase</label>
                            <select class="form-control form-control-custom" id="editPhase" name="phase">
                                <option value="menu">Menu</option>
                                <option value="launch">Launch</option>
                                <option value="travel">Travel</option>
                                <option value="landed">Landed</option>
                                <option value="exploring">Exploring</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editXp" class="form-label text-muted small fw-bold">XP</label>
                        <input type="number" class="form-control form-control-custom" id="editXp" name="xp">
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-dark px-4" id="savePlayerBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-body text-center p-5">
                <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-inline-flex p-4 mb-3">
                    <i class="fas fa-trash-alt fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">Delete Player?</h4>
                <p class="text-muted mb-4">Are you sure you want to delete this player? This action cannot be undone.</p>
                <input type="hidden" id="deletePlayerId">
                <div class="d-flex justify-content-center gap-3">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .form-control-custom {
        border: 1px solid #e9ecef;
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .form-control-custom:focus {
        background-color: #fff;
        border-color: #000;
        box-shadow: 0 0 0 3px rgba(0,0,0,0.08);
        outline: none;
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
    .action-btn.delete:hover {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .table > :not(caption) > * > * {
        padding: 0.75rem 0.75rem;
        vertical-align: middle;
    }

    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 10;
        background-color: #fff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .crew-badge {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 20px;
        padding: 2px 10px;
        font-size: 0.7rem;
        display: inline-block;
        margin: 1px;
    }

    .resource-bar {
        display: flex;
        gap: 4px;
        align-items: center;
        font-size: 0.75rem;
    }
    .resource-bar .dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        display: inline-block;
    }

    #playersTableWrapper::-webkit-scrollbar {
        width: 6px;
    }
    #playersTableWrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    #playersTableWrapper::-webkit-scrollbar-thumb {
        background: #d1d1d1;
        border-radius: 10px;
    }
    #playersTableWrapper::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    .achievement-badge {
        background: #e9ecef;
        border: 1px solid #dee2e6;
        border-radius: 20px;
        padding: 4px 12px;
        font-size: 0.8rem;
        display: inline-block;
        margin: 2px;
    }
    .achievement-badge.success {
        background: #d4edda;
        border-color: #c3e6cb;
        color: #155724;
    }
</style>
@endsection

@push('scripts')
<script>
let editingPlayerId = null;
let viewModalInstance = null;

document.addEventListener('DOMContentLoaded', function() {
    loadPlayers();
});

async function loadPlayers() {
    try {
        const response = await fetch('/admin/api/players');
        const data = await response.json();

        if (data.success) {
            renderPlayers(data.data);
        } else {
            showToast('Failed to load players', 'danger');
        }
    } catch (error) {
        console.error('Error loading players:', error);
        showToast('Error loading players', 'danger');
    }
}

function renderPlayers(players) {
    const tbody = document.getElementById('playersTableBody');
    const recordCount = document.getElementById('recordCount');
    const scrollIndicator = document.getElementById('scrollIndicator');
    const tableWrapper = document.getElementById('playersTableWrapper');

    // Update record count
    if (players && players.length > 0) {
        recordCount.textContent = `(${players.length} records)`;
    } else {
        recordCount.textContent = '(0 records)';
    }

    // Show/hide scroll indicator based on record count
    if (players && players.length > 10) {
        tableWrapper.style.maxHeight = '600px';
        tableWrapper.style.overflowY = 'auto';
        scrollIndicator.style.display = 'block';
    } else {
        tableWrapper.style.maxHeight = 'none';
        tableWrapper.style.overflowY = 'visible';
        scrollIndicator.style.display = 'none';
    }

    if (!players || players.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                        <p class="mb-0 fw-bold">No players found</p>
                        <small>Players will appear here once they register</small>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    players.forEach((p, index) => {
        // Format crew
        let crewHtml = '';
        if (p.crew && p.crew.length > 0) {
            p.crew.slice(0, 3).forEach(member => {
                crewHtml += `<span class="crew-badge">${escapeHtml(member.name)} (${escapeHtml(member.role)})</span>`;
            });
            if (p.crew.length > 3) {
                crewHtml += `<span class="crew-badge text-muted">+${p.crew.length - 3} more</span>`;
            }
        } else {
            crewHtml = '<span class="text-muted">—</span>';
        }

        // Format resources
        const resources = p.resources || {};

        html += `
            <tr>
                <td class="text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-size: 1.2rem; border: 2px solid #e9ecef;">
                            <i class="fas fa-user text-muted"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">${escapeHtml(p.username)}</div>
                            <small class="text-muted" style="font-size: 0.65rem;">${escapeHtml(p.id.substring(0, 12))}...</small>
                        </div>
                    </div>
                </td>
                <td><span class="badge bg-secondary">${escapeHtml(p.destination || 'moon')}</span></td>
                <td><span class="fw-bold text-dark">${p.xp || 0}</span></td>
                <td><span class="text-muted small">${escapeHtml(p.phase || 'menu')}</span></td>
                <td>${crewHtml}</td>
                <td>
                    <div class="resource-bar">
                        <span title="Energy"><span class="dot" style="background: #ffc107;"></span> ${resources.energy || 0}</span>
                        <span title="Food"><span class="dot" style="background: #28a745;"></span> ${resources.food || 0}</span>
                        <span title="Water"><span class="dot" style="background: #17a2b8;"></span> ${resources.water || 0}</span>
                        <span title="Oxygen"><span class="dot" style="background: #007bff;"></span> ${resources.oxygen || 0}</span>
                    </div>
                </td>
                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="action-btn" onclick="viewPlayer('${p.id}')" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn" onclick="editPlayer('${p.id}')" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="confirmDelete('${p.id}')" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

async function viewPlayer(id) {
    const content = document.getElementById('viewPlayerContent');
    content.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    viewModalInstance = new bootstrap.Modal(document.getElementById('viewPlayerModal'));
    viewModalInstance.show();

    try {
        const response = await fetch(`/admin/api/players/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;

            // Format crew
            let crewHtml = '';
            if (p.crew && p.crew.length > 0) {
                crewHtml = p.crew.map(m =>
                    `<div class="bg-light p-2 rounded-3 mb-2 d-flex justify-content-between align-items-center">
                        <span><strong>${escapeHtml(m.name)}</strong> <span class="text-muted small">(${escapeHtml(m.role)})</span></span>
                        <span class="badge bg-dark">Skill: ${m.skillLevel || 1}</span>
                    </div>`
                ).join('');
            } else {
                crewHtml = '<p class="text-muted">No crew members</p>';
            }

            // Format achievements
            let achievementsHtml = '';
            if (p.achievements && p.achievements.length > 0) {
                achievementsHtml = p.achievements.map(a =>
                    `<span class="achievement-badge success">${escapeHtml(a)}</span>`
                ).join('');
            } else {
                achievementsHtml = '<span class="text-muted">No achievements</span>';
            }

            // Format discoveries
            let discoveriesHtml = '';
            if (p.discoveries && p.discoveries.length > 0) {
                discoveriesHtml = p.discoveries.map(d =>
                    `<li class="mb-1"><i class="fas fa-star text-warning me-2"></i>${escapeHtml(d)}</li>`
                ).join('');
            } else {
                discoveriesHtml = '<li class="text-muted">No discoveries</li>';
            }

            // Format built modules
            let modulesHtml = '';
            if (p.builtModules && p.builtModules.length > 0) {
                modulesHtml = p.builtModules.map(m =>
                    `<span class="badge bg-info me-1">${escapeHtml(m)}</span>`
                ).join('');
            } else {
                modulesHtml = '<span class="text-muted">No modules built</span>';
            }

            content.innerHTML = `
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px; font-size: 4rem; border: 4px solid #f8f9fa;">
                        <i class="fas fa-user text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="fw-bold mb-1">${escapeHtml(p.username)}</h4>
                    <div class="mb-2">
                        <span class="badge bg-secondary">${escapeHtml(p.destination || 'moon')}</span>
                        <span class="badge bg-dark">${escapeHtml(p.phase || 'menu')}</span>
                        <span class="badge bg-warning text-dark">XP: ${p.xp || 0}</span>
                    </div>
                    <small class="text-muted">ID: ${escapeHtml(p.id)}</small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-users me-2"></i>Crew</h6>
                            ${crewHtml}
                        </div>

                        <div class="bg-light p-3 rounded-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-trophy me-2"></i>Achievements</h6>
                            <div>${achievementsHtml}</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-boxes me-2"></i>Resources</h6>
                            <div class="row">
                                <div class="col-6"><strong>Energy:</strong> ${p.resources?.energy || 0}</div>
                                <div class="col-6"><strong>Food:</strong> ${p.resources?.food || 0}</div>
                                <div class="col-6"><strong>Water:</strong> ${p.resources?.water || 0}</div>
                                <div class="col-6"><strong>Oxygen:</strong> ${p.resources?.oxygen || 0}</div>
                                <div class="col-6"><strong>Materials:</strong> ${p.resources?.materials || 0}</div>
                            </div>
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-rocket me-2"></i>Launch Site</h6>
                            <div><strong>Name:</strong> ${escapeHtml(p.launchSite?.name || 'N/A')}</div>
                            <div><strong>Region:</strong> ${escapeHtml(p.launchSite?.region || 'N/A')}</div>
                            <div><strong>Coordinates:</strong> ${p.launchSite?.latitude || 0}, ${p.launchSite?.longitude || 0}</div>
                        </div>

                        <div class="bg-light p-3 rounded-3">
                            <h6 class="fw-bold mb-2"><i class="fas fa-cube me-2"></i>Built Modules</h6>
                            <div>${modulesHtml}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mt-3">
                    <h6 class="fw-bold mb-2"><i class="fas fa-microscope me-2"></i>Discoveries</h6>
                    <ul class="list-unstyled mb-0">
                        ${discoveriesHtml}
                    </ul>
                </div>
            `;

            document.getElementById('editFromViewBtn').onclick = () => editPlayer(id);
        } else {
            content.innerHTML = `
                <div class="text-center text-danger py-5">
                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                    <p>Failed to load player details.</p>
                </div>
            `;
            showToast('Failed to load details', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        content.innerHTML = `
            <div class="text-center text-danger py-5">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <p>Error loading player details.</p>
            </div>
        `;
        showToast('Error loading details', 'danger');
    }
}

async function editPlayer(id) {
    try {
        const response = await fetch(`/admin/api/players/${id}`);
        const data = await response.json();

        if (data.success) {
            const p = data.data;
            editingPlayerId = id;

            document.getElementById('editPlayerId').value = id;
            document.getElementById('editUsername').value = p.username || '';
            document.getElementById('editDestination').value = p.destination || 'moon';
            document.getElementById('editPhase').value = p.phase || 'menu';
            document.getElementById('editXp').value = p.xp || 0;

            if (viewModalInstance) viewModalInstance.hide();

            const modal = new bootstrap.Modal(document.getElementById('editPlayerModal'));
            modal.show();
        } else {
            showToast('Failed to load player data', 'danger');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('Error loading player data', 'danger');
    }
}

document.getElementById('savePlayerBtn').addEventListener('click', async function() {
    const id = document.getElementById('editPlayerId').value;
    const payload = {
        username: document.getElementById('editUsername').value,
        destination: document.getElementById('editDestination').value,
        phase: document.getElementById('editPhase').value,
        xp: parseInt(document.getElementById('editXp').value) || 0,
    };

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

        const response = await fetch(`/admin/api/players/${id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('editPlayerModal'));
            modal.hide();
            loadPlayers();
        } else {
            showToast(data.message || 'Failed to update', 'danger');
        }
    } catch (error) {
        console.error('Error updating:', error);
        showToast('Error updating player', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = 'Save Changes';
    }
});

function confirmDelete(id) {
    document.getElementById('deletePlayerId').value = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
    const id = document.getElementById('deletePlayerId').value;

    try {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

        const response = await fetch(`/admin/api/players/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            }
        });

        const data = await response.json();

        if (data.success) {
            showToast('Player deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
            modal.hide();
            loadPlayers();
        } else {
            showToast('Failed to delete player', 'danger');
        }
    } catch (error) {
        console.error('Error deleting:', error);
        showToast('Error deleting player', 'danger');
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-trash me-1"></i> Delete';
    }
});

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
