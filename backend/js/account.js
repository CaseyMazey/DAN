/* ============================================================
   ACCOUNT - SIGNATUR CANVAS
============================================================ */
(() => {
    const toggleBtn = document.getElementById('profileShowCanvas');
    const wrap = document.getElementById('profileCanvasWrap');
    const canvas = document.getElementById('profileSignatureCanvas');
    const clearBtn = document.getElementById('profileClearSignature');
    const submitBtn = document.getElementById('profileSubmitSignature');

    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let drawing = false;

    canvas.addEventListener('mousedown', e => {
        drawing = true;
        ctx.beginPath();
        ctx.moveTo(e.offsetX, e.offsetY);
    });

    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('mouseleave', () => drawing = false);

    canvas.addEventListener('mousemove', e => {
        if (!drawing) return;
        ctx.lineTo(e.offsetX, e.offsetY);
        ctx.stroke();
    });

    if (toggleBtn && wrap) {
        toggleBtn.addEventListener('click', () => {
            wrap.classList.remove('d-none');
            toggleBtn.classList.add('d-none');
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', () =>
            ctx.clearRect(0, 0, canvas.width, canvas.height)
        );
    }

    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            const data = new URLSearchParams({
                save_drawn_signature: '1',
                signature_data: canvas.toDataURL('image/png')
            });

            fetch('account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: data.toString()
            })
            .then(res => {
                if (!res.ok) throw new Error('Request fehlgeschlagen');
                return res.text();
            })
            .then(() => {
                setTimeout(() => location.reload(), 200);
            })
            .catch(err => {
                console.error(err);
                alert('Signatur konnte nicht gespeichert werden.');
            });

            });
    }
})();

/* ============================================================
   ACCOUNT - DARKMODE
============================================================ */
(() => {
    const toggle = document.getElementById('themeToggle');
    const root = document.documentElement;

    if (!toggle) return;

    const stored = localStorage.getItem('danTheme') === 'dark' ? 'dark' : 'light';
    root.classList.toggle('theme-dark', stored === 'dark');
    toggle.checked = stored === 'dark';

    toggle.addEventListener('change', () => {
        const mode = toggle.checked ? 'dark' : 'light';
        root.classList.toggle('theme-dark', mode === 'dark');
        localStorage.setItem('danTheme', mode);
    });
})();

/* ============================================================
   ACCOUNT - PASSWORT MODAL (AJAX)
============================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('changePasswordModal');
    if (!modalEl) return;

    const form = modalEl.querySelector('#passwordForm');
    const btn  = form.querySelector('[data-password-submit]');
    const oldPw = form.querySelector('[name="current_password"]');
    const newPw = form.querySelector('[name="new_password"]');
    const confirmPw = form.querySelector('[name="confirm_password"]');
    const msg = modalEl.querySelector('#passwordInlineMessage');

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const showError = (input, text) => {
        input.classList.add('is-invalid');
        msg.textContent = text;
        msg.classList.remove('d-none');
    };

    const clearErrors = () => {
        form.querySelectorAll('.is-invalid').forEach(i => i.classList.remove('is-invalid'));
        msg.textContent = '';
        msg.classList.add('d-none');
    };

    btn.addEventListener('click', async () => {
        console.log('Passwort-Check gestartet');
        clearErrors();

        // 1) altes Passwort prüfen
        let res = await fetch('account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax_check_password: '1',
                current_password: oldPw.value
            })
        });

        let json = await res.json();
        if (!json.ok) {
            showError(oldPw, json.message);
            return;
        }

        // 2) neue Passwörter vergleichen
        if (newPw.value !== confirmPw.value) {
            showError(confirmPw, 'Die eingegebenen neuen Passwörter stimmen nicht überein.');
            return;
        }

        // 3) speichern
        res = await fetch('account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                ajax_change_password: '1',
                current_password: oldPw.value,
                new_password: newPw.value,
                confirm_password: confirmPw.value
            })
        });

        json = await res.json();
        if (!json.ok) {
            showError(confirmPw, json.message || 'Speichern fehlgeschlagen.');
            return;
        }

        modal.hide();
        location.reload();
    });
});
