@extends('layouts.admin')

@section('title', 'Articles Management')

@section('content')
<div class="container-fluid py-2">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0"><i class="fas fa-newspaper me-2"></i>Articles Management</h4>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-dark btn-sm d-flex align-items-center" onclick="openAddModal()">
                <i class="fas fa-plus me-2"></i>Add Article
            </button>
            <button class="btn btn-outline-dark btn-sm d-flex align-items-center" onclick="window.location.href='{{ route('admin.dashboard') }}'">
                <i class="fas fa-arrow-left me-2"></i>Back
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="row mb-4">
        <div class="col-md-5 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control border-start-0"
                       id="searchArticles"
                       placeholder="Search by title, category, or author..."
                       style="border-left: none; border-radius: 0 10px 10px 0;">
            </div>
        </div>
        <div class="col-md-4 col-lg-3">
            <select id="categoryFilter" class="form-select form-control-custom" style="border-radius: 10px;">
                <option value="">All Categories</option>
            </select>
        </div>
        <div class="col-md-3 col-lg-5 text-md-end">
            <span class="text-muted small" id="recordCount">Loading...</span>
        </div>
    </div>

    <!-- SKELETON LOADING -->
    <div id="articles-skeleton" class="row g-4">
        @for($i = 0; $i < 8; $i++)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="skeleton-box w-100" style="height: 350px; border-radius: 10px;"></div>
            </div>
        @endfor
    </div>

    <!-- ACTUAL CONTENT -->
    <div id="articles-content" class="row g-4" style="display: none; animation: fadeIn 0.5s ease-in-out;">
        <!-- Cards injected via JS -->
    </div>
</div>

{{-- Add/Edit Article Drawer (Right) --}}
<div class="offcanvas offcanvas-end fact-drawer" tabindex="-1" id="articleModal" aria-labelledby="articleModalTitle">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold" id="articleModalTitle">Add Article</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2">
        <form id="articleForm">
            <input type="hidden" id="articleId" name="articleId">

            <div class="mb-3">
                <label for="title" class="form-label text-muted small fw-bold">Title *</label>
                <input type="text" class="form-control form-control-custom" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="slug" class="form-label text-muted small fw-bold">Slug</label>
                <input type="text" class="form-control form-control-custom" id="slug" name="slug" placeholder="auto-generated-from-title">
                <small class="text-muted">URL-friendly identifier. Leave blank to auto-generate.</small>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="category" class="form-label text-muted small fw-bold">Category</label>
                    <input type="text" class="form-control form-control-custom" id="category" name="category" placeholder="e.g., Space Exploration">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="author" class="form-label text-muted small fw-bold">Author</label>
                    <input type="text" class="form-control form-control-custom" id="author" name="author" placeholder="e.g., Raindolf Owusu">
                </div>
            </div>

            <div class="mb-3">
                <label for="image" class="form-label text-muted small fw-bold">Cover Image</label>
                <div class="mb-2">
                    <div id="imagePreviewWrapper" class="position-relative d-inline-block" style="display: none;">
                        <img id="imagePreview" src="" alt="Preview" class="rounded border bg-light" style="width: 88px; height: 88px; object-fit: cover;">
                        <button type="button" id="removeImageBtn" title="Remove image" class="btn btn-sm btn-light border rounded-circle position-absolute top-0 end-0" style="width: 22px; height: 22px; padding: 0; font-size: 0.7rem; line-height: 1;">&times;</button>
                    </div>
                    <div id="imageEmptyState" class="text-muted small">
                        <i class="fas fa-image me-1 opacity-50"></i>No image selected yet
                    </div>
                </div>
                <input type="url" class="form-control form-control-custom mb-2" id="image" name="image" placeholder="https://res.cloudinary.com/...">
                <div class="d-flex align-items-center gap-2">
                    <input type="file" id="imageFile" accept="image/*" class="d-none">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="uploadImageBtn">
                        <i class="fas fa-cloud-upload-alt me-1"></i>Upload from device
                    </button>
                    <span id="imageUploadStatus" class="small text-muted"></span>
                </div>
                <small class="text-muted">Paste a URL, or upload a file (JPG, PNG, GIF, WebP — max 10 MB)</small>
            </div>

            <div class="mb-3">
                <label for="excerpt" class="form-label text-muted small fw-bold">Excerpt</label>
                <textarea class="form-control form-control-custom" id="excerpt" name="excerpt" rows="2"
                          placeholder="Short summary shown on cards"></textarea>
            </div>

            <div class="mb-3">
                <label for="content" class="form-label text-muted small fw-bold">Content *</label>
                <textarea class="form-control" id="content" name="content" rows="14"
                          placeholder="Write your article..."></textarea>
                <small class="text-muted">
                    <i class="fas fa-pen me-1"></i>Use the toolbar to format: headings, bold, italic, links, images, lists, tables.
                </small>
            </div>
        </form>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Cancel</button>
            <button type="button" class="btn btn-dark px-4" id="saveArticleBtn">Save Article</button>
        </div>
    </div>
</div>

{{-- View Article Drawer (Right) --}}
<div class="offcanvas offcanvas-end fact-drawer" tabindex="-1" id="viewArticleModal" aria-labelledby="viewArticleTitle">
    <div class="offcanvas-header border-0 pb-0">
        <h5 class="offcanvas-title fw-bold" id="viewArticleTitle"><i class="fas fa-info-circle me-2 text-dark"></i>Article Details</h5>
        <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body pt-2" id="viewArticleContent">
        <div class="text-center py-5">
            <div class="spinner-border text-dark" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div class="offcanvas-footer border-0 pt-0 px-4 pb-4">
        <div class="d-flex gap-2 justify-content-end">
            <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">Close</button>
            <button type="button" class="btn btn-outline-dark px-4" id="editFromViewBtn">
                <i class="fas fa-edit me-1"></i> Edit
            </button>
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
                <h4 class="fw-bold mb-2">Delete Article?</h4>
                <p class="text-muted mb-4">
                    Are you sure you want to delete this article? This action cannot be undone.
                </p>
                <input type="hidden" id="deleteArticleId">
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
    @keyframes skeleton-pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }
    .skeleton-box { display: block; background-color: #e9ecef; border-radius: 4px; animation: skeleton-pulse 1.5s infinite ease-in-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .form-control-custom { border: 1px solid #e9ecef; background-color: #f8f9fa; border-radius: 10px; padding: 0.75rem 1rem; font-size: 0.9rem; transition: all 0.2s ease; }
    .form-control-custom:focus { background-color: #fff; border-color: #000; box-shadow: 0 0 0 3px rgba(0,0,0,0.08); outline: none; }

    .fact-card {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        height: 350px;
        background: #f8f9fa;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .fact-card:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important; }
    .card-img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 1; transition: transform 0.4s ease; }
    .fact-card:hover .card-img { transform: scale(1.08); }
    .card-actions-overlay { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; opacity: 0; transition: opacity 0.3s ease; z-index: 10; }
    .fact-card:hover .card-actions-overlay { opacity: 1; }
    .card-action-btn {
        width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;
        border-radius: 50%; border: none; background: rgba(255,255,255,0.9); color: #333;
        backdrop-filter: blur(4px); transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .card-action-btn:hover { background: #fff; color: #000; transform: scale(1.1); }
    .card-action-btn.delete:hover { background: #dc3545; color: #fff; }
    .card-overlay-content {
        position: absolute; bottom: 0; left: 0; right: 0;
        padding: 1.25rem 1.25rem 2.5rem 1.25rem; z-index: 2;
        background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.75) 40%, rgba(0,0,0,0) 100%);
        color: #fff; min-width: 0;
    }
    .text-truncate-1, .text-truncate-2 {
        display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;
        text-overflow: ellipsis; max-width: 100%; overflow-wrap: anywhere;
    }
    .text-truncate-1 { -webkit-line-clamp: 1; }
    .text-truncate-2 { -webkit-line-clamp: 2; }
    .category-badge {
        display: inline-block; max-width: 100%; white-space: nowrap; overflow: hidden;
        text-overflow: ellipsis; font-size: 0.7rem; font-weight: 500;
        padding: 0.35rem 0.6rem; border-radius: 6px; line-height: 1.2;
        background: rgba(255,255,255,0.92); color: #212529;
        margin-bottom: 0.5rem; vertical-align: middle;
    }
    .card-desc-text {
        font-size: 0.9rem !important; font-weight: 500 !important; color: #ffffff !important;
        text-shadow: 0 2px 8px rgba(0,0,0,0.8); line-height: 1.4; margin-bottom: 0; margin-top: 0.5rem;
    }
    .card-meta {
        display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;
        font-size: 0.72rem; color: rgba(255,255,255,0.85); flex-wrap: wrap;
    }
    .card-meta span {
        display: inline-flex; align-items: center; gap: 4px; max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    .article-content-preview {
        max-height: 500px; overflow-y: auto; padding: 1.25rem 1.5rem;
        background: #fff; border-radius: 12px; border: 1px solid #f1f1f1;
        font-size: 0.95rem; line-height: 1.7; color: #2c2c2c;
    }
    .article-content-preview::-webkit-scrollbar { width: 8px; }
    .article-content-preview::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .article-content-preview::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }
    .article-content-preview h1 { font-size: 1.75rem; font-weight: 700; margin: 1.25rem 0 0.75rem; color: #111; }
    .article-content-preview h2 { font-size: 1.5rem; font-weight: 700; margin: 1.25rem 0 0.75rem; color: #111; }
    .article-content-preview h3 { font-size: 1.3rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #222; }
    .article-content-preview h4 { font-size: 1.15rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #222; }
    .article-content-preview h5 { font-size: 1rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #333; }
    .article-content-preview h6 { font-size: 0.9rem; font-weight: 600; margin: 1rem 0 0.5rem; color: #333; }
    .article-content-preview p { margin: 0 0 1rem; }
    .article-content-preview img {
        max-width: 100%; height: auto; border-radius: 8px; margin: 1rem 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .article-content-preview figure { margin: 1.5rem 0; text-align: center; }
    .article-content-preview figcaption {
        font-size: 0.85rem; color: #6c757d; margin-top: 0.5rem; font-style: italic;
    }
    .article-content-preview a { color: #0d6efd; text-decoration: underline; }
    .article-content-preview a:hover { color: #0a58ca; }
    .article-content-preview blockquote {
        border-left: 4px solid #dee2e6; padding: 0.5rem 0 0.5rem 1.25rem;
        color: #6c757d; font-style: italic; margin: 1.25rem 0; background: #f8f9fa; border-radius: 4px;
    }
    .article-content-preview ul, .article-content-preview ol {
        padding-left: 1.5rem; margin: 0 0 1rem;
    }
    .article-content-preview li { margin-bottom: 0.35rem; }
    .article-content-preview ul li { list-style: disc; }
    .article-content-preview ol li { list-style: decimal; }
    .article-content-preview pre {
        background: #1e1e1e; color: #f8f8f2; padding: 1rem; border-radius: 8px;
        overflow-x: auto; font-size: 0.85rem; margin: 1rem 0;
    }
    .article-content-preview code {
        background: #f1f1f1; padding: 0.15rem 0.4rem; border-radius: 4px;
        font-size: 0.85em; font-family: 'SF Mono', Menlo, Consolas, monospace;
    }
    .article-content-preview pre code { background: transparent; padding: 0; color: inherit; }
    .article-content-preview table {
        width: 100%; border-collapse: collapse; margin: 1.25rem 0; font-size: 0.9rem;
    }
    .article-content-preview th, .article-content-preview td {
        border: 1px solid #dee2e6; padding: 0.6rem 0.85rem; text-align: left;
    }
    .article-content-preview th { background: #f8f9fa; font-weight: 600; }
    .article-content-preview hr { border: 0; border-top: 1px solid #dee2e6; margin: 1.5rem 0; }
    .article-content-preview strong { font-weight: 700; color: #111; }
    .article-content-preview em { font-style: italic; }

    .fact-drawer {
        width: 560px !important; max-width: 92vw;
        border-left: none !important;
        box-shadow: -8px 0 30px rgba(0,0,0,0.12);
    }
    .fact-drawer .offcanvas-header { padding: 1.5rem 1.5rem 0.5rem 1.5rem; }
    .fact-drawer .offcanvas-body { padding: 0.5rem 1.5rem 1.5rem 1.5rem; flex-grow: 1; overflow-y: auto; }
    .fact-drawer .offcanvas-footer { background-color: #fff; border-top: 1px solid #f1f1f1; padding-top: 1rem !important; }

    .tox-tinymce { border-radius: 10px !important; border-color: #e9ecef !important; }
    .tox .tox-toolbar__primary { border-top-left-radius: 10px !important; border-top-right-radius: 10px !important; }
</style>
@endsection

@push('scripts')
<!-- TinyMCE -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>

<script>
// ---------------------------------------------------------------------------
// Guard against duplicate execution.
// Even if @stack('scripts') is rendered more than once by an ancestor layout,
// this script tag will only execute its body the first time. All state and
// functions are declared inside the IIFE so they never leak to `window`
// except for the four inline-onclick handlers we explicitly export.
// ---------------------------------------------------------------------------
if (window.__articlesAdminScriptLoaded) {
    console.warn('[articles] duplicate script execution detected — skipping');
} else {
    (function () {
        'use strict';

        window.__articlesAdminScriptLoaded = true;

        // ---------------------------------------------------------------
        // State
        // ---------------------------------------------------------------
        let editingArticleId = null;
        let viewDrawerInstance = null;
        let allArticles = [];
        let filteredArticles = [];
        let articleEditor = null;
        let tinyMceReady = false;

        const IMAGE_UPLOAD_URL = '/admin/api/images/upload';

        // DOM refs
        const imageInputEl        = document.getElementById('image');
        const imageFileInput      = document.getElementById('imageFile');
        const imagePreviewEl      = document.getElementById('imagePreview');
        const imagePreviewWrapper = document.getElementById('imagePreviewWrapper');
        const imageEmptyState     = document.getElementById('imageEmptyState');
        const imageUploadStatus   = document.getElementById('imageUploadStatus');
        const uploadImageBtn      = document.getElementById('uploadImageBtn');

        // ---------------------------------------------------------------
        // Cover image
        // ---------------------------------------------------------------
        uploadImageBtn.addEventListener('click', () => imageFileInput.click());

        document.getElementById('removeImageBtn').addEventListener('click', () => {
            imageInputEl.value = '';
            clearImagePreview();
        });

        imageInputEl.addEventListener('change', () => showImagePreview(imageInputEl.value));
        imagePreviewEl.addEventListener('error', () => clearImagePreview());

        function showImagePreview(url) {
            if (!url) { clearImagePreview(); return; }
            imagePreviewEl.src = url;
            imagePreviewWrapper.style.display = 'inline-block';
            imageEmptyState.style.display = 'none';
        }

        function clearImagePreview() {
            imagePreviewEl.removeAttribute('src');
            imagePreviewWrapper.style.display = 'none';
            imageEmptyState.style.display = 'block';
        }

        imageFileInput.addEventListener('change', async function () {
            const file = this.files[0]; this.value = '';
            if (!file) return;
            if (!file.type.startsWith('image/')) { showToast('Please choose an image file', 'danger'); return; }
            if (file.size > 10240 * 1024) { showToast('Image is too large (max 10 MB)', 'danger'); return; }

            const reader = new FileReader();
            reader.onload = (e) => showImagePreview(e.target.result);
            reader.readAsDataURL(file);

            imageUploadStatus.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Uploading…';
            uploadImageBtn.disabled = true;

            const fd = new FormData();
            fd.append('image', file);
            fd.append('folder', 'articles');

            try {
                const response = await fetch(IMAGE_UPLOAD_URL, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: fd,
                });
                const data = await response.json();
                console.log('[Image upload] response:', data);

                if (data.success && data.data && data.data.url) {
                    imageInputEl.value = data.data.url;
                    showImagePreview(data.data.url);
                    imageUploadStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> Uploaded';
                    showToast('Image uploaded', 'success');
                } else {
                    clearImagePreview();
                    imageUploadStatus.textContent = '';
                    showToast(data.message || 'Upload failed', 'danger');
                }
            } catch (err) {
                console.error('[Image upload] error:', err);
                clearImagePreview();
                imageUploadStatus.textContent = '';
                showToast('Error uploading image', 'danger');
            } finally {
                uploadImageBtn.disabled = false;
                setTimeout(() => { imageUploadStatus.textContent = ''; }, 5000);
            }
        });

        // ---------------------------------------------------------------
        // TinyMCE
        // ---------------------------------------------------------------
        function destroyTinyMCE() {
            try {
                if (articleEditor) articleEditor.remove();
            } catch (e) { console.warn('TinyMCE remove error:', e); }
            articleEditor = null;
            tinyMceReady = false;
        }

        function initTinyMCE() {
            destroyTinyMCE();

            const textarea = document.getElementById('content');
            if (!textarea) return;

            tinymce.init({
                selector: '#content',
                height: 480,
                menubar: 'file edit view insert format tools table',
                plugins: [
                    'advlist','autolink','lists','link','image','charmap','preview',
                    'anchor','searchreplace','visualblocks','code','fullscreen',
                    'insertdatetime','media','table','wordcount','help','pagebreak','emoticons'
                ],
                toolbar:
                    'undo redo | blocks | bold italic underline strikethrough | ' +
                    'forecolor backcolor | alignleft aligncenter alignright alignjustify | ' +
                    'bullist numlist outdent indent | link image media table | ' +
                    'pagebreak emoticons | removeformat | code fullscreen',
                block_formats:
                    'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; ' +
                    'Heading 4=h4; Heading 5=h5; Heading 6=h6; Preformatted=pre; Blockquote=blockquote',
                branding: false,
                promotion: false,
                entity_encoding: 'raw',
                convert_urls: false,
                paste_data_images: true,
                image_advtab: true,
                image_title: true,
                automatic_uploads: true,
                file_picker_types: 'image',
                link_default_target: '_blank',
                link_title: true,
                images_upload_handler: function (blobInfo, progress) {
                    return new Promise(function (resolve, reject) {
                        const fd = new FormData();
                        fd.append('image', blobInfo.blob(), blobInfo.filename());
                        fd.append('folder', 'articles');

                        fetch('/admin/api/images/upload', {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: fd
                        })
                        .then(r => r.json())
                        .then(data => {
                            console.log('[TinyMCE image upload] response:', data);
                            if (data.success && data.data && data.data.url) {
                                resolve(data.data.url);
                            } else {
                                reject({ message: data.message || 'Upload failed' });
                            }
                        })
                        .catch(err => {
                            console.error('[TinyMCE image upload] error:', err);
                            reject({ message: 'Network error' });
                        });
                    });
                },
                file_picker_callback: function (cb, value, meta) {
                    if (meta.filetype !== 'image') return;

                    const input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    input.style.display = 'none';

                    input.onchange = function () {
                        const file = this.files[0];
                        if (!file) return;

                        const reader = new FileReader();
                        reader.onload = function () {
                            const id = 'blobid' + (new Date()).getTime();
                            const blobCache = tinymce.activeEditor.editorUpload.blobCache;
                            const base64 = reader.result.split(',')[1];
                            const blobInfo = blobCache.create(id, file, base64, file.name);
                            blobCache.add(blobInfo);
                            cb(blobInfo.blobUri(), { title: file.name });
                        };
                        reader.readAsDataURL(file);
                    };

                    input.click();
                },
                setup: function (editor) {
                    articleEditor = editor;

                    editor.on('init', function () {
                        tinyMceReady = true;
                        console.log('[TinyMCE] ✅ initialized');
                    });

                    editor.on('change', function () {
                        editor.save();
                    });
                }
            });
        }

        function setEditorContent(html) {
            if (!html) html = '';
            const trySet = (attempts = 0) => {
                const editor = tinymce.get('content');
                if (editor && editor.initialized) {
                    editor.setContent(html);
                    editor.save();
                    console.log('[TinyMCE] content set, length:', html.length);
                } else if (attempts < 20) {
                    setTimeout(() => trySet(attempts + 1), 100);
                } else {
                    console.warn('[TinyMCE] ⚠️ could not set content — falling back to textarea');
                    document.getElementById('content').value = html;
                }
            };
            trySet();
        }

        function getEditorContent() {
            if (typeof tinymce === 'undefined') {
                console.error('[TinyMCE] ❌ library not loaded — tinymce is undefined');
                return null;
            }

            const editor = tinymce.get('content');
            if (editor && editor.initialized) {
                editor.save();
                const html = editor.getContent();

                console.groupCollapsed('[Save] 📝 TinyMCE getContent()');
                console.log('Length:', html.length);
                console.log('Has <img>:', /<img\b/i.test(html));
                console.log('Has src="":', /src="/i.test(html));
                console.log('Has <strong>/<b>:', /<(strong|b)\b/i.test(html));
                console.log('Has <h1>-<h6>:', /<h[1-6]\b/i.test(html));
                console.log('Has style="":', /style="/i.test(html));
                console.log('Has href="":', /href="/i.test(html));
                console.log('First 500 chars:', html.substring(0, 500));
                console.groupEnd();

                return html;
            }

            if (editor && !editor.initialized) {
                console.warn('[TinyMCE] ⚠️ editor exists but is not yet initialized');
                return null;
            }

            const tv = document.getElementById('content')?.value || '';
            console.warn('[TinyMCE] ⚠️ no editor instance — using textarea fallback, length:', tv.length);
            return tv;
        }

        // ---------------------------------------------------------------
        // App init
        // ---------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', function () {
            loadArticles();
            setupFilters();

            const articleDrawer = document.getElementById('articleModal');
            articleDrawer.addEventListener('shown.bs.offcanvas', function () {
                if (!tinyMceReady) initTinyMCE();
            });
            articleDrawer.addEventListener('hidden.bs.offcanvas', function () {
                destroyTinyMCE();
            });
        });

        function setupFilters() {
            document.getElementById('searchArticles')?.addEventListener('input', applyFilters);
            document.getElementById('categoryFilter')?.addEventListener('change', applyFilters);
        }

        function applyFilters() {
            const q = document.getElementById('searchArticles').value.toLowerCase().trim();
            const cat = document.getElementById('categoryFilter').value;

            filteredArticles = allArticles.filter(a => {
                const title = (a.title || '').toLowerCase();
                const category = (a.category || '').toLowerCase();
                const author = (a.author || '').toLowerCase();
                const matchesQ = !q || title.includes(q) || category.includes(q) || author.includes(q);
                const matchesCat = !cat || a.category === cat;
                return matchesQ && matchesCat;
            });

            renderArticles(filteredArticles);
            updateFilteredCount(filteredArticles.length);
        }

        function updateFilteredCount(count) {
            const el = document.getElementById('recordCount');
            if (el) el.textContent = count === 1 ? '1 article found' : count + ' articles found';
        }

        async function loadArticles() {
            const skeleton = document.getElementById('articles-skeleton');
            const content = document.getElementById('articles-content');

            try {
                const response = await fetch('/admin/api/articles');
                const data = await response.json();
                console.log('[loadArticles] response count:', (data.data || []).length);

                if (data.success) {
                    allArticles = data.data || [];
                    filteredArticles = allArticles;

                    const cats = [...new Set(allArticles.map(a => a.category).filter(Boolean))];
                    const sel = document.getElementById('categoryFilter');
                    sel.innerHTML = '<option value="">All Categories</option>' +
                        cats.map(c => `<option value="${escapeHtml(c)}">${escapeHtml(c)}</option>`).join('');

                    renderArticles(filteredArticles);
                    updateFilteredCount(filteredArticles.length);
                } else {
                    showToast('Failed to load articles', 'danger');
                    renderArticles([]);
                    updateFilteredCount(0);
                }
            } catch (err) {
                console.error('[loadArticles] error:', err);
                showToast('Error loading articles', 'danger');
                renderArticles([]);
                updateFilteredCount(0);
            } finally {
                if (skeleton) skeleton.style.display = 'none';
                if (content) content.style.display = 'flex';
            }
        }

        function renderArticles(articles) {
            const grid = document.getElementById('articles-content');

            if (!articles || articles.length === 0) {
                grid.innerHTML = `
                    <div class="col-12 text-center py-5">
                        <div class="text-muted">
                            <i class="fas fa-newspaper fa-2x mb-3 d-block opacity-50"></i>
                            <p class="mb-0 fw-bold">${allArticles.length > 0 ? 'No matching articles found' : 'No articles found'}</p>
                            <small>${allArticles.length > 0 ? 'Try adjusting your search or filter' : 'Click "Add Article" to create one'}</small>
                        </div>
                    </div>`;
                return;
            }

            let html = '';
            articles.forEach(a => {
                const imgSrc = a.image || 'https://via.placeholder.com/400x300?text=No+Image';
                const catBadge = a.category
                    ? `<span class="category-badge" title="${escapeHtml(a.category)}"><i class="fas fa-tag me-1"></i>${escapeHtml(a.category)}</span>`
                    : '';

                const rawText = a.excerpt ? a.excerpt : (a.title || '');
                const plainExcerpt = rawText.replace(/<[^>]+>/g, '').substring(0, 140);

                html += `
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <div class="fact-card">
                            <img src="${escapeHtml(imgSrc)}" alt="${escapeHtml(a.title)}" class="card-img">

                            <div class="card-actions-overlay">
                                <button class="card-action-btn" onclick="viewArticle('${escapeHtml(a.id)}')" title="View"><i class="fas fa-eye"></i></button>
                                <button class="card-action-btn" onclick="editArticle('${escapeHtml(a.id)}')" title="Edit"><i class="fas fa-edit"></i></button>
                                <button class="card-action-btn delete" onclick="confirmDelete('${escapeHtml(a.id)}')" title="Delete"><i class="fas fa-trash"></i></button>
                            </div>

                            <div class="card-overlay-content">
                                ${catBadge}
                                <h5 class="fw-bold mb-0 text-white text-truncate-1">${escapeHtml(a.title)}</h5>
                                <p class="card-desc-text text-truncate-2">${escapeHtml(plainExcerpt)}</p>
                                <div class="card-meta">
                                    <span><i class="fas fa-user"></i>${escapeHtml(a.author || '—')}</span>
                                    ${a.readingTime ? `<span><i class="fas fa-clock"></i>${escapeHtml(a.readingTime)}</span>` : ''}
                                    <span><i class="fas fa-eye"></i>${a.viewcount || 0}</span>
                                </div>
                            </div>
                        </div>
                    </div>`;
            });

            grid.innerHTML = html;
        }

        function openAddModal() {
            editingArticleId = null;
            document.getElementById('articleModalTitle').textContent = 'Add Article';
            document.getElementById('articleForm').reset();
            document.getElementById('articleId').value = '';
            document.getElementById('slug').value = '';
            document.getElementById('content').value = '';
            clearImagePreview();
            imageUploadStatus.textContent = '';
            document.getElementById('saveArticleBtn').textContent = 'Save Article';

            const drawer = new bootstrap.Offcanvas(document.getElementById('articleModal'));
            drawer.show();
        }

        async function editArticle(id) {
            try {
                const response = await fetch(`/admin/api/articles/${id}`);
                const data = await response.json();

                if (data.success) {
                    const a = data.data;
                    editingArticleId = id;

                    console.groupCollapsed('[Edit] 📥 Article fetched');
                    console.log('id:', id);
                    console.log('content.length:', (a.content || '').length);
                    console.log('has <img>:', /<img\b/i.test(a.content || ''));
                    console.log('has src="":', /src="/i.test(a.content || ''));
                    console.log('has style="":', /style="/i.test(a.content || ''));
                    console.log('preview:', (a.content || '').substring(0, 500));
                    console.groupEnd();

                    document.getElementById('articleModalTitle').textContent = 'Edit Article';
                    document.getElementById('articleId').value = id;
                    document.getElementById('title').value = a.title || '';
                    document.getElementById('slug').value = a.slug || '';
                    document.getElementById('category').value = a.category || '';
                    document.getElementById('author').value = a.author || '';
                    document.getElementById('excerpt').value = a.excerpt || '';
                    document.getElementById('content').value = a.content || '';
                    document.getElementById('image').value = a.image || '';
                    showImagePreview(a.image || '');
                    imageUploadStatus.textContent = '';
                    document.getElementById('saveArticleBtn').textContent = 'Update Article';

                    if (viewDrawerInstance) viewDrawerInstance.hide();
                    const drawer = new bootstrap.Offcanvas(document.getElementById('articleModal'));
                    drawer.show();

                    const setupContent = () => {
                        const editor = tinymce.get('content');
                        if (editor && editor.initialized) {
                            editor.setContent(a.content || '');
                            editor.save();
                            console.log('[Edit] ✅ content loaded into editor, length:', (a.content || '').length);

                            setTimeout(() => {
                                const ed = tinymce.get('content');
                                if (ed) {
                                    const now = ed.getContent();
                                    console.groupCollapsed('[Edit] 📝 editor content AFTER setContent');
                                    console.log('length:', now.length);
                                    console.log('has <img>:', /<img\b/i.test(now));
                                    console.log('preview:', now.substring(0, 500));
                                    console.groupEnd();
                                }
                            }, 100);
                        } else {
                            setTimeout(setupContent, 100);
                        }
                    };
                    setTimeout(setupContent, 300);
                } else {
                    showToast('Failed to load article', 'danger');
                }
            } catch (err) {
                console.error('[editArticle] error:', err);
                showToast('Error loading article', 'danger');
            }
        }

        async function viewArticle(id) {
            const content = document.getElementById('viewArticleContent');
            content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-dark" role="status"><span class="visually-hidden">Loading...</span></div></div>`;
            viewDrawerInstance = new bootstrap.Offcanvas(document.getElementById('viewArticleModal'));
            viewDrawerInstance.show();

            try {
                const response = await fetch(`/admin/api/articles/${id}`);
                const data = await response.json();

                if (data.success) {
                    const a = data.data;

                    console.groupCollapsed('[View] 📥 Article fetched');
                    console.log('id:', id);
                    console.log('content.length:', (a.content || '').length);
                    console.log('has <img>:', /<img\b/i.test(a.content || ''));
                    console.log('has src="":', /src="/i.test(a.content || ''));
                    console.log('preview:', (a.content || '').substring(0, 500));
                    console.groupEnd();

                    const imgSrc = a.image || 'https://via.placeholder.com/480x300?text=No+Image';
                    const contentHtml = a.content || '<em class="text-muted">No content</em>';

                    content.innerHTML = `
                        <div class="mb-4">
                            <img src="${escapeHtml(imgSrc)}" class="w-100 mb-4 shadow-sm" style="height: 240px; object-fit: cover; border-radius: 12px;" alt="${escapeHtml(a.title || '')}">
                            <div class="text-center">
                                <h4 class="fw-bold mb-1">${escapeHtml(a.title)}</h4>
                                ${a.category ? `<p class="text-muted mb-1"><i class="fas fa-tag me-2"></i>${escapeHtml(a.category)}</p>` : ''}
                                ${a.author ? `<p class="text-muted mb-1"><i class="fas fa-user-edit me-2"></i>By ${escapeHtml(a.author)}</p>` : ''}
                                <p class="text-muted small mb-3">
                                    ${a.readingTime ? `${escapeHtml(a.readingTime)} · ` : ''}${a.viewcount || 0} views
                                </p>
                            </div>
                        </div>

                        ${a.excerpt ? `
                            <div class="bg-light p-3 rounded-3 mb-3">
                                <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Excerpt</small>
                                <p class="mb-0 fst-italic" style="font-size: 0.9rem;">${escapeHtml(a.excerpt.replace(/<[^>]+>/g, ''))}</p>
                            </div>
                        ` : ''}

                        <small class="text-muted d-block text-uppercase mb-2 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">Content</small>
                        <div class="article-content-preview">${contentHtml}</div>
                    `;
                    document.getElementById('editFromViewBtn').onclick = () => editArticle(id);
                } else {
                    content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Failed to load article details.</p></div>`;
                }
            } catch (err) {
                console.error('[viewArticle] error:', err);
                content.innerHTML = `<div class="text-center text-danger py-5"><i class="fas fa-exclamation-circle fa-2x mb-2"></i><p>Error loading article details.</p></div>`;
            }
        }

        function confirmDelete(id) {
            document.getElementById('deleteArticleId').value = id;
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }

        // ---------------------------------------------------------------
        // Save
        // ---------------------------------------------------------------
        document.getElementById('saveArticleBtn').addEventListener('click', async function () {
            const form = document.getElementById('articleForm');
            const formData = new FormData(form);
            const id = document.getElementById('articleId').value;

            let contentValue;
            try {
                contentValue = getEditorContent();
            } catch (err) {
                console.error('[Save] unexpected error reading editor content:', err);
                showToast('The editor hit an unexpected error. Please refresh the page and try again.', 'danger');
                return;
            }

            if (contentValue === null) {
                showToast('The editor is still loading — wait a moment and click Save again.', 'danger');
                return;
            }

            const payload = {
                title:    formData.get('title'),
                slug:     formData.get('slug'),
                category: formData.get('category'),
                author:   formData.get('author'),
                excerpt:  formData.get('excerpt'),
                content:  contentValue,
                image:    formData.get('image'),
            };

            console.groupCollapsed('[Save] 📤 Payload being sent');
            console.log('URL:', id ? `/admin/api/articles/${id}` : '/admin/api/articles', '| method:', id ? 'PUT' : 'POST');
            console.log('Title:', payload.title);
            console.log('content.length:', (payload.content || '').length);
            console.log('content has <img>:', /<img\b/i.test(payload.content || ''));
            console.log('content has src="":', /src="/i.test(payload.content || ''));
            console.log('content has <strong>:', /<strong\b/i.test(payload.content || ''));
            console.log('content has style="":', /style="/i.test(payload.content || ''));
            console.log('content preview:', (payload.content || '').substring(0, 500));
            console.groupEnd();

            if (!payload.title || !payload.title.trim()) {
                showToast('Title is required', 'danger');
                return;
            }

            if (payload.content.replace(/<[^>]+>/g, '').trim() === '') {
                showToast('Content cannot be empty', 'danger');
                return;
            }

            if (this.disabled) return;

            try {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';

                let url = '/admin/api/articles';
                let method = 'POST';
                if (id) { url = `/admin/api/articles/${id}`; method = 'PUT'; }

                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                console.groupCollapsed('[Save] 📥 Server response');
                console.log('HTTP status:', response.status);
                console.log('body:', data);
                console.groupEnd();

                if (data.success) {
                    showToast(data.message, 'success');
                    const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('articleModal'));
                    if (drawer) drawer.hide();
                    loadArticles();
                } else {
                    showToast(data.message || 'Failed to save', 'danger');
                }
            } catch (err) {
                console.error('[Save] error:', err);
                showToast('Error saving article', 'danger');
            } finally {
                this.disabled = false;
                this.innerHTML = id ? 'Update Article' : 'Save Article';
            }
        });

        // ---------------------------------------------------------------
        // Delete
        // ---------------------------------------------------------------
        document.getElementById('confirmDeleteBtn').addEventListener('click', async function () {
            const id = document.getElementById('deleteArticleId').value;

            if (this.disabled) return;

            try {
                this.disabled = true;
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';

                const response = await fetch(`/admin/api/articles/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                });
                const data = await response.json();

                if (data.success) {
                    showToast('Article deleted', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                    if (modal) modal.hide();
                    loadArticles();
                } else {
                    showToast('Failed to delete', 'danger');
                }
            } catch (err) {
                console.error('[delete] error:', err);
                showToast('Error deleting', 'danger');
            } finally {
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-trash me-1"></i> Delete';
            }
        });

        // ---------------------------------------------------------------
        // Toast
        // ---------------------------------------------------------------
        function showToast(message, type = 'info') {
            let toastContainer = document.getElementById('toastContainer');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toastContainer';
                toastContainer.className = 'position-fixed bottom-0 end-0 p-3';
                toastContainer.style.zIndex = '9999';
                document.body.appendChild(toastContainer);
            }
            const toastId = 'toast-' + Date.now();
            const toastHtml = `
                <div id="${toastId}" class="toast show align-items-center text-white bg-${type === 'danger' ? 'danger' : 'dark'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body"><i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${escapeHtml(message)}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>`;
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            setTimeout(() => {
                const toast = document.getElementById(toastId);
                if (toast) toast.remove();
            }, 5000);
        }

        // ---------------------------------------------------------------
        // Utils
        // ---------------------------------------------------------------
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }


        window.openAddModal    = openAddModal;
        window.editArticle     = editArticle;
        window.viewArticle     = viewArticle;
        window.confirmDelete   = confirmDelete;

        console.log('[articles] script loaded ✅');
    })();
}
</script>
@endpush
