window.SWUpload = {
    async upload(file, folder) {
        const cfg = window.SW_SUPABASE;
        if (!cfg) throw new Error('SW_SUPABASE not configured');

        const ext = (file.name.split('.').pop() || 'bin').toLowerCase();
        const rand = Math.random().toString(36).slice(2, 10);
        const filename = 'f_' + Date.now() + '_' + rand + '.' + ext;
        const path = folder + '/' + filename;

        const res = await fetch(
            cfg.url + '/storage/v1/object/' + cfg.bucket + '/' + path,
            {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + cfg.key,
                    'Content-Type': file.type || 'application/octet-stream',
                    'x-upsert': 'true',
                    'cache-control': '3600'
                },
                body: file
            }
        );

        if (!res.ok) {
            const err = await res.text();
            throw new Error('Supabase upload failed: ' + err);
        }

        return {
            url:      cfg.url + '/storage/v1/object/public/' + cfg.bucket + '/' + path,
            filename: filename,
            path:     path
        };
    },

    /** Attach handler to a form: intercepts submit, uploads file, sets hidden URL field */
    bindForm(formId, fileInputName, folder, hiddenName) {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            const fi = form.querySelector('input[name="' + fileInputName + '"]');
            if (!fi || !fi.files || fi.files.length === 0) return; // no file, normal submit

            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn ? btn.innerHTML : '';
            if (btn) { btn.disabled = true; btn.innerHTML = '⏳ Uploading...'; }

            try {
                const result = await window.SWUpload.upload(fi.files[0], folder);
                // Add/replace hidden field with URL
                let hidden = form.querySelector('input[name="' + hiddenName + '"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = hiddenName;
                    form.appendChild(hidden);
                }
                hidden.value = result.url;
                // Clear file input so PHP doesn't try to handle it
                fi.value = '';
                form.submit();
            } catch (err) {
                alert('Upload error: ' + err.message);
                if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            }
        });
    }
};
