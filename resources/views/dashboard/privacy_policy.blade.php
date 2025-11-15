@extends('dashboard.layout.app')

@section('content')
<div class="main-content p-4" style="margin-left: 250px; max-width: calc(100% - 250px);margin-top: 50px;">
    <h4>Privacy Policy</h4>

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


function initEditor(lang) {

    
    if (tinymce.get('privacyEditor')) {
        tinymce.get('privacyEditor').remove();
    }

    tinymce.init({
        selector: '#privacyEditor',
        height: 400,
        menubar: false,

        
        plugins: 'link lists code directionality paste',

      
        toolbar: 'undo redo | bold italic underline | bullist numlist | link | ltr rtl | code',

        skin: 'oxide-dark',
        content_css: false,

        directionality: lang === 'ar' ? 'rtl' : 'ltr',

        content_style: `
            html, body {
                background: transparent !important;
                background-color: transparent !important;
                color: #fff !important;
                direction: ${lang === 'ar' ? 'rtl' : 'ltr'};
                text-align: ${lang === 'ar' ? 'right' : 'left'};
                font-family: 'Cairo', sans-serif;
            }
        `,

        setup: function(editor) {

            editor.on('init', function () {

                const iframe = editor.iframeElement;
                if (iframe) {
                    iframe.style.background = 'transparent';
                    iframe.style.backgroundColor = 'transparent';
                }

                editor.getBody().style.background = "transparent";
                editor.getBody().style.backgroundColor = "transparent";

                loadContent(lang);
            });

            editor.on('paste', (event) => {
                const clipboard = (event.clipboardData || event.originalEvent?.clipboardData);
                if (!clipboard) return;

                for (let i = 0; i < clipboard.items.length; i++) {
                    if (clipboard.items[i].type.indexOf("image") !== -1) {
                        event.preventDefault();
                        alert("Images are not allowed.");
                        return false;
                    }
                }
            });

            
            editor.on('BeforeSetContent', (e) => {
                if (e.content.includes("<img")) {
                    e.preventDefault();
                    alert("Images are not allowed.");
                }
            });

            editor.on('BeforePaste', (e) => {
                if (e.content.includes("<img")) {
                    e.preventDefault();
                    alert("Images are not allowed.");
                }
            });
        },

        
    });
}


function loadContent(lang) {
    fetch(`/admin-dashboard/privacy-policy/${lang}`)
        .then(res => res.json())
        .then(data => {
            tinymce.get('privacyEditor').setContent(data.value || '');
        });
}


document.getElementById('langSwitcher').addEventListener('change', (e) => {
    currentLang = e.target.value;
    initEditor(currentLang);
});


document.getElementById('saveBtn').addEventListener('click', () => {
    const content = tinymce.get('privacyEditor').getContent();

    fetch('/admin-dashboard/privacy-policy/update', {
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


initEditor(currentLang);

</script>
@endsection
