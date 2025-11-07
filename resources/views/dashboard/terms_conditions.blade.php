@extends('dashboard.layout.app')

@section('content')
<div class="main-content p-4" style="margin-left: 250px; max-width: calc(100% - 250px);margin-top: 50px;">
<h4>Terms & Conditions</h4>


<div class="mb-3">
    <label class="form-label fw-bold">Select Language:</label>
    <select id="langSwitcher" name="lang" class="form-control" style="width: 10%;">
        <option value="en">English</option>
        <option value="ar">Arabic</option>
    </select>
</div>

    <textarea id="privacyEditor" class="form-control" rows="12"></textarea>
    <div class="text-center mt-4" style="padding-bottom: 10px; margin-bottom: 25px;">
        <button id="saveBtn" class="btn btn-primary px-5 py-2">Save</button>
    </div>
</div>

<script src="https://cdn.tiny.cloud/1/orchmjkwdde0ld7ft8cgo8n3nhma90mpok6mgtiumdwwmclc/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>

<script>
let currentLang = 'en';

tinymce.init({
    selector: '#privacyEditor',
    height: 400,
    menubar: false,
    plugins: 'link lists code',
    toolbar: 'undo redo | bold italic underline | bullist numlist | link | code',
    skin: 'oxide-dark', 
    content_css: false,
    content_style: `
      html, body {
        background: transparent !important;
        color: #fff !important;
      }
    `,
    setup: function (editor) {
        editor.on('init', function () {
            const iframe = editor.iframeElement;
            if (iframe) {
                iframe.style.background = 'transparent';
            }
            loadContent(currentLang);
        });
    }
});

function loadContent(lang) {
    fetch(`/admin-dashboard/terms-conditions/${lang}`)
        .then(res => res.json())
        .then(data => {
            tinymce.get('privacyEditor').setContent(data.value || '');
        });
}

document.getElementById('langSwitcher').addEventListener('change', (e) => {
    currentLang = e.target.value;
    loadContent(currentLang);
});

document.getElementById('saveBtn').addEventListener('click', () => {
    const content = tinymce.get('privacyEditor').getContent();

    fetch('/admin-dashboard/terms-conditions/update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ lang: currentLang, value: content })
    })
    .then(res => res.json())
    .then(data => alert(data.message || 'Saved!'));
});

loadContent(currentLang);
</script>
@endsection
