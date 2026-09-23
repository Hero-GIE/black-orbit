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

    <div class="row mb-4">
        <div class="col-md-5 col-lg-4">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="searchArticles"
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

    <div id="articles-skeleton" class="row g-4">
        @for($i = 0; $i < 8; $i++)
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                <div class="skeleton-box w-100" style="height: 350px; border-radius: 10px;"></div>
            </div>
        @endfor
    </div>

    <div id="articles-content" class="row g-4" style="display: none; animation: fadeIn 0.5s ease-in-out;"></div>
</div>

{{-- Add/Edit Article Drawer --}}
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
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label text-muted small fw-bold mb-0">Content *</label>
                    <button type="button" id="clearContentBtn" class="btn btn-outline-danger btn-sm"
                            style="font-size: 0.72rem; padding: 2px 10px;">
                        <i class="fas fa-eraser me-1"></i>Clear content
                    </button>
                </div>

                <div id="quillToolbar" class="rounded-top" style="border: 1px solid #e9ecef; border-bottom: none; background: #fff;">
                    <span class="ql-formats">
                        <select class="ql-header">
                            <option value="1">Heading 1</option>
                            <option value="2">Heading 2</option>
                            <option value="3">Heading 3</option>
                            <option value="4">Heading 4</option>
                            <option selected>Normal</option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold" type="button"></button>
                        <button class="ql-italic" type="button"></button>
                        <button class="ql-underline" type="button"></button>
                        <button class="ql-strike" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <select class="ql-color"></select>
                        <select class="ql-background"></select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered" type="button"></button>
                        <button class="ql-list" value="bullet" type="button"></button>
                        <button class="ql-indent" value="-1" type="button"></button>
                        <button class="ql-indent" value="+1" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-blockquote" type="button"></button>
                        <button class="ql-code-block" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-link" type="button"></button>
                        <button class="ql-image" type="button"></button>
                        <button class="ql-video" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-align" value="" type="button"></button>
                        <button class="ql-align" value="center" type="button"></button>
                        <button class="ql-align" value="right" type="button"></button>
                        <button class="ql-align" value="justify" type="button"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-clean" type="button"></button>
                    </span>
                </div>

                <div id="quillEditor"
                     style="min-height: 320px; background: #fff; border: 1px solid #e9ecef; border-radius: 0 0 10px 10px; font-size: 0.95rem; line-height: 1.55;"></div>

                <textarea id="content" name="content" class="d-none"></textarea>

                <small class="text-muted d-block mt-2">
                    <i class="fas fa-info-circle me-1"></i>
                    Press <kbd>Enter</kbd> for a new paragraph, <kbd>Shift</kbd>+<kbd>Enter</kbd> for a soft line break.
                    Long pasted text is auto-split into paragraphs.
                    <strong>Hover over any image</strong> to remove it.
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

{{-- View Article Drawer --}}
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
                <p class="text-muted mb-4">Are you sure you want to delete this article? This action cannot be undone.</p>
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

    .fact-card { position: relative; border-radius: 10px; overflow: hidden; height: 350px; background: #f8f9fa; box-shadow: 0 4px 15px rgba(0,0,0,0.05); transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .fact-card:hover { transform: translateY(-5px); box-shadow: 0 1rem 3rem rgba(0,0,0,0.175) !important; }
    .card-img { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; z-index: 1; transition: transform 0.4s ease; }
    .fact-card:hover .card-img { transform: scale(1.08); }
    .card-actions-overlay { position: absolute; top: 15px; right: 15px; display: flex; gap: 8px; opacity: 0; transition: opacity 0.3s ease; z-index: 10; }
    .fact-card:hover .card-actions-overlay { opacity: 1; }
    .card-action-btn { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; border: none; background: rgba(255,255,255,0.9); color: #333; backdrop-filter: blur(4px); transition: all 0.2s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
    .card-action-btn:hover { background: #fff; color: #000; transform: scale(1.1); }
    .card-action-btn.delete:hover { background: #dc3545; color: #fff; }
    .card-overlay-content { position: absolute; bottom: 0; left: 0; right: 0; padding: 1.25rem 1.25rem 2.5rem 1.25rem; z-index: 2; background: linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.75) 40%, rgba(0,0,0,0) 100%); color: #fff; min-width: 0; }
    .text-truncate-1, .text-truncate-2 { display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis; max-width: 100%; overflow-wrap: anywhere; }
    .text-truncate-1 { -webkit-line-clamp: 1; }
    .text-truncate-2 { -webkit-line-clamp: 2; }
    .category-badge { display: inline-block; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.7rem; font-weight: 500; padding: 0.35rem 0.6rem; border-radius: 6px; line-height: 1.2; background: rgba(255,255,255,0.92); color: #212529; margin-bottom: 0.5rem; vertical-align: middle; }
    .card-desc-text { font-size: 0.9rem !important; font-weight: 500 !important; color: #ffffff !important; text-shadow: 0 2px 8px rgba(0,0,0,0.8); line-height: 1.4; margin-bottom: 0; margin-top: 0.5rem; }
    .card-meta { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem; font-size: 0.72rem; color: rgba(255,255,255,0.85); flex-wrap: wrap; }
    .card-meta span { display: inline-flex; align-items: center; gap: 4px; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .article-content-preview { max-height: 500px; overflow-y: auto; padding: 1.25rem 1.5rem; background: #fff; border-radius: 12px; border: 1px solid #f1f1f1; font-size: 0.95rem; line-height: 1.55; color: #2c2c2c; }
    .article-content-preview::-webkit-scrollbar { width: 8px; }
    .article-content-preview::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    .article-content-preview::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 10px; }

    .article-content-preview h1 { font-size: 1.75rem; font-weight: 700; margin: 1.25rem 0 0.75rem; color: #111; }
    .article-content-preview h2 { font-size: 1.5rem;  font-weight: 700; margin: 1.25rem 0 0.75rem; color: #111; }
    .article-content-preview h3 { font-size: 1.3rem;  font-weight: 600; margin: 1rem 0 0.5rem;   color: #222; }
    .article-content-preview h4 { font-size: 1.15rem; font-weight: 600; margin: 1rem 0 0.5rem;   color: #222; }
    .article-content-preview p  { margin: 0 0 1rem; }
    .article-content-preview strong { font-weight: 700; color: #111; }
    .article-content-preview em { font-style: italic; }
    .article-content-preview u { text-decoration: underline; }
    .article-content-preview s { text-decoration: line-through; }
    .article-content-preview a { color: #0d6efd; text-decoration: underline; }
    .article-content-preview a:hover { color: #0a58ca; }

    .article-content-preview img { max-width: 100%; height: auto; display: block; margin: 1rem auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .article-content-preview blockquote { border-left: 4px solid #dee2e6; padding: 0.5rem 0 0.5rem 1.25rem; color: #6c757d; font-style: italic; margin: 1.25rem 0; background: #f8f9fa; border-radius: 4px; }
    .article-content-preview ul, .article-content-preview ol { padding-left: 1.5rem; margin: 0 0 1rem; }
    .article-content-preview li { margin-bottom: 0.35rem; }
    .article-content-preview ol, .article-content-preview ul { list-style: none; padding-left: 1.5rem; }
    .article-content-preview li[data-list] { position: relative; padding-left: 1.4rem; list-style: none; }
    .article-content-preview li[data-list]::before { position: absolute; left: 0; top: 0; font-weight: 500; color: #333; }
    .article-content-preview li[data-list="bullet"]::before { content: "•"; }
    .article-content-preview li[data-list="ordered"]::before { content: attr(data-list-value, "1.") " "; }
    .article-content-preview .ql-ui { display: none !important; }
    .article-content-preview .ql-indent-1 { margin-left: 2rem; }
    .article-content-preview .ql-indent-2 { margin-left: 4rem; }
    .article-content-preview .ql-indent-3 { margin-left: 6rem; }
    .article-content-preview .ql-align-center  { text-align: center; }
    .article-content-preview .ql-align-right   { text-align: right; }
    .article-content-preview .ql-align-justify { text-align: justify; }
    .article-content-preview pre, .article-content-preview pre.ql-syntax { background: #1e1e1e; color: #f8f8f2; padding: 1rem 1.25rem; border-radius: 8px; overflow-x: auto; font-size: 0.85rem; font-family: 'SF Mono', Menlo, Consolas, monospace; margin: 1rem 0; white-space: pre; }
    .article-content-preview code { background: #f1f1f1; padding: 0.15rem 0.4rem; border-radius: 4px; font-size: 0.85em; font-family: 'SF Mono', Menlo, Consolas, monospace; }
    .article-content-preview pre code { background: transparent; padding: 0; color: inherit; }
    .article-content-preview iframe, .article-content-preview video { max-width: 100%; width: 100%; aspect-ratio: 16 / 9; height: auto; border: 0; border-radius: 8px; margin: 1rem 0; }
    .article-content-preview hr { border: 0; border-top: 1px solid #dee2e6; margin: 1.5rem 0; }

    .fact-drawer { width: 560px !important; max-width: 92vw; border-left: none !important; box-shadow: -8px 0 30px rgba(0,0,0,0.12); }
    .fact-drawer .offcanvas-header { padding: 1.5rem 1.5rem 0.5rem 1.5rem; }
    .fact-drawer .offcanvas-body { padding: 0.5rem 1.5rem 1.5rem 1.5rem; flex-grow: 1; overflow-y: auto; }
    .fact-drawer .offcanvas-footer { background-color: #fff; border-top: 1px solid #f1f1f1; padding-top: 1rem !important; }

    #quillToolbar.ql-toolbar.ql-snow, #quillEditor.ql-container.ql-snow { border-color: #e9ecef; }
    #quillToolbar.ql-toolbar.ql-snow { border-radius: 10px 10px 0 0; background: #fff; padding: 6px 8px; border-bottom: none; }
    #quillEditor.ql-container.ql-snow { border-radius: 0 0 10px 10px; font-family: 'Segoe UI', system-ui, sans-serif; font-size: 0.95rem; }
    #quillToolbar.ql-toolbar.ql-snow .ql-formats { margin-right: 8px; }
    .ql-editor.ql-blank::before { color: #adb5bd; font-style: italic; }
    .ql-editor h1 { font-size: 1.75rem; font-weight: 700; margin: 1rem 0 0.75rem; }
    .ql-editor h2 { font-size: 1.5rem;  font-weight: 700; margin: 1rem 0 0.75rem; }
    .ql-editor h3 { font-size: 1.3rem;  font-weight: 600; margin: 1rem 0 0.5rem; }
    .ql-editor h4 { font-size: 1.15rem; font-weight: 600; margin: 1rem 0 0.5rem; }
    .ql-editor blockquote { border-left: 4px solid #dee2e6; padding-left: 1rem; color: #6c757d; font-style: italic; margin: 1rem 0; }
    .ql-editor img { max-width: 100%; height: auto; border-radius: 8px; margin: 1rem auto; display: block; }
    .ql-editor pre.ql-syntax { background: #1e1e1e; color: #f8f8f2; border-radius: 8px; padding: 1rem; }
    .ql-editor .ql-video { width: 100%; aspect-ratio: 16/9; height: auto; border-radius: 8px; margin: 1rem 0; }

    /* ---------- Image removal affordances in editor ---------- */
    .ql-editor img { cursor: pointer; transition: outline 0.15s ease, box-shadow 0.15s ease; border-radius: 8px; }
    .ql-editor img:hover { outline: 2px dashed #dc3545; outline-offset: 3px; box-shadow: 0 4px 16px rgba(220, 53, 69, 0.15); }
    .ql-editor img.ql-image-selected, .ql-editor .ql-editor-image-selected { outline: 2px solid #0d6efd; outline-offset: 3px; }

    /* ==============================================================
       Paragraph consistency — editor and view modal render identically
       ============================================================== */
    .ql-editor p,
    .article-content-preview p {
        margin: 0 0 1rem;
        line-height: 1.55;
    }
    .ql-editor > *:first-child,
    .article-content-preview > *:first-child {
        margin-top: 0;
    }
    .ql-editor p:empty,
    .ql-editor p:has(> br:only-child),
    .article-content-preview p:empty,
    .article-content-preview p:has(> br:only-child) {
        min-height: 1em;
        margin: 0 0 1rem;
    }
    .ql-editor h1, .ql-editor h2, .ql-editor h3, .ql-editor h4,
    .article-content-preview h1,
    .article-content-preview h2,
    .article-content-preview h3,
    .article-content-preview h4 {
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .ql-editor ul, .ql-editor ol,
    .article-content-preview ul,
    .article-content-preview ol {
        padding-left: 1.5rem;
        margin: 0 0 1rem;
    }
    .ql-editor li,
    .article-content-preview li {
        margin-bottom: 0.35rem;
    }
    .ql-editor blockquote,
    .article-content-preview blockquote {
        border-left: 4px solid #dee2e6;
        padding: 0.5rem 0 0.5rem 1.25rem;
        color: #6c757d;
        font-style: italic;
        margin: 1rem 0;
        background: #f8f9fa;
        border-radius: 4px;
    }
</style>
@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.min.js"></script>

<script>
if (window.__articlesAdminScriptLoaded) {
    console.warn('[articles] duplicate script execution detected — skipping');
} else {
    (function () {
        'use strict';
        window.__articlesAdminScriptLoaded = true;

        const Delta = Quill.import('delta');

        let editingArticleId   = null;
        let viewDrawerInstance = null;
        let allArticles        = [];
        let filteredArticles   = [];
        let quill              = null;
        let quillReady         = false;

        const IMAGE_UPLOAD_URL = '/admin/api/images/upload';

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

            try {
                const url = await uploadImageToServer(file);
                imageInputEl.value = url;
                showImagePreview(url);
                imageUploadStatus.innerHTML = '<i class="fas fa-check-circle text-success"></i> Uploaded';
                showToast('Image uploaded', 'success');
            } catch (err) {
                console.error('[Cover image upload] error:', err);
                clearImagePreview();
                imageUploadStatus.textContent = '';
                showToast(err.message || 'Upload failed', 'danger');
            } finally {
                uploadImageBtn.disabled = false;
                setTimeout(() => { imageUploadStatus.textContent = ''; }, 5000);
            }
        });

        async function uploadImageToServer(file) {
            const fd = new FormData();
            fd.append('image', file);
            fd.append('folder', 'articles');

            const response = await fetch(IMAGE_UPLOAD_URL, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: fd,
            });

            const data = await response.json();
            console.log('[Image upload] server response:', data);

            if (data.success && data.data && data.data.url) {
                return data.data.url;
            }
            throw new Error(data.message || 'Upload failed');
        }

        // ---------------------------------------------------------------
        // Quill init
        // ---------------------------------------------------------------
        function initQuill() {
            if (quillReady) return;
            const container = document.getElementById('quillEditor');
            if (!container) return;

            const BlockEmbed = Quill.import('blots/block/embed');
            class CustomImageBlot extends BlockEmbed {
                static create(value) {
                    const node = super.create();
                    if (typeof value === 'object' && value !== null) {
                        node.setAttribute('src', value.src || '');
                        if (value.alt)    node.setAttribute('alt', value.alt);
                        if (value.width)  node.setAttribute('width', value.width);
                        if (value.height) node.setAttribute('height', value.height);
                    } else {
                        node.setAttribute('src', value);
                        node.setAttribute('alt', '');
                    }
                    node.setAttribute('loading', 'lazy');
                    return node;
                }
                static value(node) {
                    return {
                        src:    node.getAttribute('src') || '',
                        alt:    node.getAttribute('alt') || '',
                        width:  node.getAttribute('width') || null,
                        height: node.getAttribute('height') || null,
                    };
                }
                static formats(node) {
                    const formats = {};
                    const w = node.getAttribute('width');
                    const h = node.getAttribute('height');
                    if (w) formats.width  = w;
                    if (h) formats.height = h;
                    return formats;
                }
                format(name, value) {
                    if (name === 'width' || name === 'height') {
                        if (value) this.domNode.setAttribute(name, value);
                        else this.domNode.removeAttribute(name);
                    } else {
                        super.format(name, value);
                    }
                }
            }
            CustomImageBlot.blotName = 'image';
            CustomImageBlot.tagName  = 'img';
            Quill.register(CustomImageBlot, true);

            quill = new Quill('#quillEditor', {
                theme: 'snow',
                placeholder: 'Write your article...',
                modules: {
                    toolbar: {
                        container: '#quillToolbar',
                        handlers: { image: quillImageHandler },
                    },
                    clipboard: {
                        matchers: [
                            [Node.TEXT_NODE, function (node, delta) {
                                if (!node.data || node.data.indexOf('\n') === -1) return delta;
                                const parts = node.data.split(/\r?\n/);
                                const out = new Delta();
                                parts.forEach((line, i) => {
                                    if (i > 0) out.insert('\n');
                                    out.insert(line);
                                });
                                return out;
                            }],
                            ['div', function (node, delta) {
                                const out = new Delta();
                                delta.ops.forEach(op => out.insert(op.insert, op.attributes));
                                out.insert('\n');
                                return out;
                            }],
                            ['span', function (node, delta) {
                                const allowed = ['bold','italic','underline','strike','color','background','link','code'];
                                const out = new Delta();
                                delta.ops.forEach(op => {
                                    if (typeof op.insert === 'string') {
                                        const attrs = {};
                                        Object.keys(op.attributes || {}).forEach(k => {
                                            if (allowed.includes(k)) attrs[k] = op.attributes[k];
                                        });
                                        out.insert(op.insert, Object.keys(attrs).length ? attrs : undefined);
                                    } else {
                                        out.insert(op.insert, op.attributes);
                                    }
                                });
                                return out;
                            }],
                        ],
                    },
                },
            });

            // Existing image-paste listener (kept)
            quill.root.addEventListener('paste', handleQuillPaste, true);

            // NEW: long-prose paste listener — auto-splits wall-of-text
            quill.root.addEventListener('paste', handleLongProsePaste, true);

            quillReady = true;
            console.log('[Quill] ✅ initialized');

            setupImageRemoval();
        }

        function quillImageHandler() {
            const choice = window.prompt(
                'Insert image:\n\n' +
                '• Paste an image URL below and click OK\n' +
                '• Or leave blank and click OK to pick a file from your device'
            );
            if (choice === null) return;

            const trimmed = choice.trim();
            if (trimmed && /^https?:\/\//i.test(trimmed)) {
                insertQuillImage(trimmed);
                return;
            }

            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.style.display = 'none';
            input.onchange = async () => {
                const file = input.files[0];
                if (!file) return;
                try {
                    showToast('Uploading image…', 'info');
                    const url = await uploadImageToServer(file);
                    insertQuillImage(url);
                    showToast('Image inserted', 'success');
                } catch (err) {
                    console.error('[Quill image upload] error:', err);
                    showToast(err.message || 'Upload failed', 'danger');
                }
            };
            input.click();
        }

        function insertQuillImage(url) {
            if (!quill) return;
            const range = quill.getSelection(true) || { index: quill.getLength() };
            quill.insertEmbed(range.index, 'image', { src: url, alt: '' }, 'user');
            quill.insertText(range.index + 1, '\n', 'user');
            quill.setSelection(range.index + 2, 0);
        }

        function handleQuillPaste(e) {
            const clipboard = e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData);
            if (!clipboard || !clipboard.items) return;

            for (let i = 0; i < clipboard.items.length; i++) {
                const item = clipboard.items[i];
                if (item.kind === 'file' && item.type.startsWith('image/')) {
                    e.preventDefault();
                    e.stopPropagation();
                    const file = item.getAsFile();
                    if (!file) continue;
                    (async () => {
                        try {
                            showToast('Uploading pasted image…', 'info');
                            const url = await uploadImageToServer(file);
                            insertQuillImage(url);
                            showToast('Image inserted', 'success');
                        } catch (err) {
                            console.error('[Quill paste upload] error:', err);
                            showToast(err.message || 'Upload failed', 'danger');
                        }
                    })();
                    return;
                }
            }
        }

        // ---------------------------------------------------------------
        // Long-prose paste — auto-split into paragraphs
        // ---------------------------------------------------------------
        function handleLongProsePaste(e) {
            const clipboard = e.clipboardData || (e.originalEvent && e.originalEvent.clipboardData);
            if (!clipboard) return;

            // If the paste contains HTML blocks, let Quill handle it.
            const htmlData = clipboard.getData('text/html');
            if (htmlData && /<(p|div|h[1-6]|ul|ol|li|br)\b/i.test(htmlData)) return;

            const text = clipboard.getData('text/plain');
            if (!text) return;

            // Skip content that already has newlines (Quill's matchers split it)
            if (/\n/.test(text.trim())) return;

            // Skip short one-liners
            if (text.length < 300) return;

            const sentences = splitIntoSentences(text);
            if (sentences.length < 4) return;

            const paragraphs = groupSentencesIntoParagraphs(sentences, {
                maxSentencesPerParagraph: 4,
                maxCharsPerParagraph: 500,
            });

            const html = paragraphs.map(p => `<p>${escapeHtmlBasic(p)}</p>`).join('');

            e.preventDefault();
            e.stopPropagation();

            const range = quill.getSelection(true) || { index: quill.getLength() };
            quill.clipboard.dangerouslyPasteHTML(range.index, html, 'user');
            quill.setSelection(range.index + html.length, 0);

            showToast(`Inserted ${paragraphs.length} paragraphs`, 'info');
        }

        // Split text into sentences, respecting common abbreviations
        function splitIntoSentences(text) {
            const abbreviations = ['Mr', 'Mrs', 'Ms', 'Dr', 'Prof', 'Inc', 'Ltd', 'Co',
                                   'vs', 'etc', 'e.g', 'i.e', 'cf', 'al', 'Jr', 'Sr',
                                   'St', 'Ave', 'No', 'Fig', 'Ph.D', 'U.S', 'U.K'];

            let protectedText = text;
            abbreviations.forEach((abbr, i) => {
                const re = new RegExp(`\\b${abbr.replace(/\./g, '\\.')}\\.`, 'g');
                protectedText = protectedText.replace(re, `__ABBR${i}__`);
            });

            const rawParts = protectedText
                .split(/(?<=[.!?])\s+/)
                .map(s => s.trim())
                .filter(s => s.length > 0);

            return rawParts.map(part => {
                let restored = part;
                abbreviations.forEach((abbr, i) => {
                    restored = restored.replace(new RegExp(`__ABBR${i}__`, 'g'), `${abbr}.`);
                });
                return restored;
            });
        }

        // Group sentences into readable paragraph-sized chunks
        function groupSentencesIntoParagraphs(sentences, opts) {
            const { maxSentencesPerParagraph, maxCharsPerParagraph } = opts;
            const paragraphs = [];
            let buffer = [];
            let bufferChars = 0;

            for (const sentence of sentences) {
                buffer.push(sentence);
                bufferChars += sentence.length + 1;

                const tooManySentences = buffer.length >= maxSentencesPerParagraph;
                const tooLong = bufferChars >= maxCharsPerParagraph;

                if (tooManySentences || tooLong) {
                    paragraphs.push(buffer.join(' '));
                    buffer = [];
                    bufferChars = 0;
                }
            }

            if (buffer.length > 0) {
                paragraphs.push(buffer.join(' '));
            }

            return paragraphs;
        }

        function escapeHtmlBasic(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        // ---------------------------------------------------------------
        // Image removal
        // ---------------------------------------------------------------
        function setupImageRemoval() {
            if (!quill) return;

            quill.root.addEventListener('dblclick', function (e) {
                if (e.target && e.target.tagName === 'IMG') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.confirm('Remove this image?')) removeImageFromQuill(e.target);
                }
            });

            quill.root.addEventListener('contextmenu', function (e) {
                if (e.target && e.target.tagName === 'IMG') {
                    e.preventDefault();
                    e.stopPropagation();
                    if (window.confirm('Remove this image from the content?')) removeImageFromQuill(e.target);
                }
            });
        }

        function removeImageFromQuill(imgEl) {
            if (!quill) return;
            const blot = Quill.find(imgEl);
            if (blot) blot.remove(); else imgEl.remove();
            syncQuillToTextarea();
            console.log('[Quill] image removed');
            showToast('Image removed', 'info');
        }

        function clearEditorContent() {
            if (!quill) {
                showToast('Editor not ready', 'danger');
                return;
            }
            if (!window.confirm('Clear all content from the editor?')) return;

            quill.setContents([{ insert: '\n' }], 'silent');
            quill.history.clear();
            syncQuillToTextarea();
            showToast('Content cleared', 'info');
        }

        // ---------------------------------------------------------------
        // Content get/set
        // ---------------------------------------------------------------
        function setQuillContent(html) {
            if (!quill) {
                console.warn('[Quill] setContent called before init');
                return;
            }

            if (!html || html === '<p><br></p>' || html === '<p></p>') {
                quill.setContents([{ insert: '\n' }], 'silent');
                quill.history.clear();
                syncQuillToTextarea();
                return;
            }

            // Legacy plain-text → wrap each line in <p>
            if (!/<[a-z][\s\S]*>/i.test(html)) {
                html = html.split(/\r?\n/)
                    .filter(line => line.trim() !== '')
                    .map(line => `<p>${line.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</p>`)
                    .join('');
            }

            const tempContainer = document.createElement('div');
            const tempQuill = new Quill(tempContainer, { modules: { toolbar: false } });
            tempQuill.clipboard.dangerouslyPasteHTML(html);
            const delta = tempQuill.getContents();

            quill.setContents(delta, 'silent');
            quill.history.clear();
            syncQuillToTextarea();

            console.log('[Quill] content loaded, ops:', delta.ops.length,
                        '| img count:', (html.match(/<img\b/gi) || []).length);
        }

        function getQuillContent() {
            if (!quill) {
                console.error('[Quill] getContent called before init');
                return null;
            }

            let html = quill.root.innerHTML;

            // Cleanup pass — same treatment the bio field gets
            html = html.replace(/<div(\s[^>]*)?>/gi, '<p>').replace(/<\/div>/gi, '</p>');
            html = html.replace(/<span\s*>\s*<\/span>/gi, '');
            html = html.replace(/\s+style="[^"]*"/gi, '');
            html = html.replace(/(<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>\s*){2,}/gi, '<p><br></p>');
            html = html.replace(/(<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>\s*)+$/i, '');
            html = html.replace(/^(?:\s*<p>(?:\s|<br\s*\/?>|&nbsp;)*<\/p>)+/i, '');

            if (/^(<p><br\s*\/?><\/p>)?\s*$/i.test(html)) html = '';

            console.groupCollapsed('[Save] 📝 Quill getContent()');
            console.log('Length:', html.length);
            console.log('Paragraph count:', (html.match(/<p\b/gi) || []).length);
            console.log('Has <img>:', /<img\b/i.test(html));
            console.log('Has src="":', /src="/i.test(html));
            console.log('First 500 chars:', html.substring(0, 500));
            console.groupEnd();

            return html;
        }

        function syncQuillToTextarea() {
            const ta = document.getElementById('content');
            if (ta && quill) ta.value = quill.root.innerHTML;
        }

        // ---------------------------------------------------------------
        // App init
        // ---------------------------------------------------------------
        document.addEventListener('DOMContentLoaded', function () {
            loadArticles();
            setupFilters();

            document.getElementById('clearContentBtn')?.addEventListener('click', clearEditorContent);

            const articleDrawer = document.getElementById('articleModal');
            articleDrawer.addEventListener('shown.bs.offcanvas', function () {
                if (!quillReady) setTimeout(initQuill, 50);
            });
        });

        function setupFilters() {
            document.getElementById('searchArticles')?.addEventListener('input', applyFilters);
            document.getElementById('categoryFilter')?.addEventListener('change', applyFilters);
        }

        function applyFilters() {
            const q   = document.getElementById('searchArticles').value.toLowerCase().trim();
            const cat = document.getElementById('categoryFilter').value;

            filteredArticles = allArticles.filter(a => {
                const title    = (a.title || '').toLowerCase();
                const category = (a.category || '').toLowerCase();
                const author   = (a.author || '').toLowerCase();
                const matchesQ   = !q || title.includes(q) || category.includes(q) || author.includes(q);
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
            const content  = document.getElementById('articles-content');

            try {
                const response = await fetch('/admin/api/articles');
                const data = await response.json();
                console.log('[loadArticles] response count:', (data.data || []).length);

                if (data.success) {
                    allArticles      = data.data || [];
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

            const clearQuill = (attempts = 0) => {
                if (quill && quillReady) {
                    quill.setContents([{ insert: '\n' }], 'silent');
                    quill.history.clear();
                    console.log('[Quill] cleared for new article');
                } else if (attempts < 30) {
                    setTimeout(() => clearQuill(attempts + 1), 100);
                }
            };
            setTimeout(clearQuill, 300);
        }

        async function editArticle(id) {
            try {
                const response = await fetch(`/admin/api/articles/${id}`);
                const data = await response.json();

                if (!data.success) {
                    showToast('Failed to load article', 'danger');
                    return;
                }

                const a = data.data;
                editingArticleId = id;

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

                const applyContent = (attempts = 0) => {
                    if (quill && quillReady) {
                        setQuillContent(a.content || '');
                    } else if (attempts < 30) {
                        setTimeout(() => applyContent(attempts + 1), 100);
                    } else {
                        console.warn('[Edit] Quill never became ready — falling back to textarea');
                        document.getElementById('content').value = a.content || '';
                    }
                };
                setTimeout(applyContent, 400);
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

                    const imgSrc = a.image || 'https://via.placeholder.com/480x300?text=No+Image';
                    let contentHtml = a.content || '<em class="text-muted">No content</em>';

                    // Legacy plain-text → wrap into paragraphs
                    if (a.content && !/<[a-z][\s\S]*>/i.test(a.content)) {
                        contentHtml = a.content
                            .split(/\r?\n/)
                            .filter(line => line.trim() !== '')
                            .map(line => `<p>${escapeHtml(line)}</p>`)
                            .join('');
                    }

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
                        <div class="article-content-preview ql-editor" style="padding: 1.25rem 1.5rem;">${contentHtml}</div>
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

            const contentValue = getQuillContent();
            if (contentValue === null) {
                showToast('The editor is still loading — wait a moment and click Save again.', 'danger');
                return;
            }
            document.getElementById('content').value = contentValue;

            const payload = {
                title:    formData.get('title'),
                slug:     formData.get('slug'),
                category: formData.get('category'),
                author:   formData.get('author'),
                excerpt:  formData.get('excerpt'),
                content:  contentValue,
                image:    formData.get('image'),
            };

            if (!payload.title || !payload.title.trim()) {
                showToast('Title is required', 'danger');
                return;
            }
            if (payload.content.replace(/<[^>]+>/g, '').trim() === '' && !/<img\b/i.test(payload.content)) {
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
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

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

                const response = await fetch(`/admin/api/articles/${id}`, { method: 'DELETE' });
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

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.openAddModal  = openAddModal;
        window.editArticle   = editArticle;
        window.viewArticle   = viewArticle;
        window.confirmDelete = confirmDelete;

        console.log('[articles] script loaded ✅');
    })();
}
</script>
@endpush
