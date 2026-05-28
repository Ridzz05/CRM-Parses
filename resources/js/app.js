const applyTheme = (theme) => {
    document.documentElement.dataset.theme = theme;
    localStorage.setItem('crm-theme', theme);

    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        const active = button.dataset.themeOption === theme;
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
        button.classList.toggle('bg-[var(--app-primary)]', active);
        button.classList.toggle('text-[var(--app-primary-text)]', active);
        button.classList.toggle('shadow-sm', active);
    });
};

const preferredTheme = () => {
    const stored = localStorage.getItem('crm-theme');

    if (stored === 'light' || stored === 'dark') {
        return stored;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const snackbar = () => document.getElementById('app-snackbar');

const snackbarIcon = (variant) => {
    if (variant === 'loading') {
        return '<span class="app-spinner"></span>';
    }

    if (variant === 'success') {
        return '✓';
    }

    if (variant === 'danger') {
        return '!';
    }

    return 'i';
};

const showSnackbar = (message, variant = 'info', autoHide = true) => {
    const element = snackbar();

    if (!element || !message) {
        return;
    }

    const messageElement = element.querySelector('[data-snackbar-message]');
    const iconElement = element.querySelector('[data-snackbar-icon]');

    element.dataset.variant = variant;
    element.dataset.open = 'true';
    messageElement.textContent = message;
    iconElement.innerHTML = snackbarIcon(variant);

    window.clearTimeout(element.hideTimer);

    if (autoHide) {
        element.hideTimer = window.setTimeout(() => {
            element.dataset.open = 'false';
        }, 4200);
    }
};

const bindSnackbars = () => {
    const element = snackbar();

    if (!element) {
        return;
    }

    element.querySelector('[data-snackbar-close]')?.addEventListener('click', () => {
        element.dataset.open = 'false';
    });

    if (element.dataset.sessionMessage) {
        showSnackbar(element.dataset.sessionMessage, element.dataset.sessionVariant || 'info');
    }

    document.querySelectorAll('form[data-loading-message]').forEach((form) => {
        form.addEventListener('submit', () => {
            showSnackbar(form.dataset.loadingMessage, 'loading', false);

            form.querySelectorAll('button[type="submit"]').forEach((button) => {
                button.disabled = true;
            });
        });
    });
};

const preferredAudioMimeType = () => {
    const candidates = [
        'audio/webm;codecs=opus',
        'audio/webm',
        'audio/ogg;codecs=opus',
        'audio/ogg',
    ];

    return candidates.find((type) => window.MediaRecorder?.isTypeSupported(type)) || '';
};

const audioExtension = (mimeType) => mimeType.includes('ogg') ? 'ogg' : 'webm';

const bindVoiceRecorder = () => {
    document.querySelectorAll('[data-voice-form]').forEach((form) => {
        const startButton = form.querySelector('[data-record-start]');
        const stopButton = form.querySelector('[data-record-stop]');
        const status = form.querySelector('[data-record-status]');
        const preview = form.querySelector('[data-record-preview]');
        const input = form.querySelector('input[type="file"][name="voice"]');

        if (!startButton || !stopButton || !status || !preview || !input) {
            return;
        }

        if (!navigator.mediaDevices?.getUserMedia || !window.MediaRecorder) {
            status.textContent = 'Browser tidak mendukung live recording. Gunakan upload file.';
            startButton.disabled = true;
            return;
        }

        let recorder = null;
        let stream = null;
        let chunks = [];

        startButton.addEventListener('click', async () => {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                const mimeType = preferredAudioMimeType();
                recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
                chunks = [];

                recorder.addEventListener('dataavailable', (event) => {
                    if (event.data.size > 0) {
                        chunks.push(event.data);
                    }
                });

                recorder.addEventListener('stop', () => {
                    const type = recorder.mimeType || mimeType || 'audio/webm';
                    const blob = new Blob(chunks, { type });
                    const extension = audioExtension(type);
                    const file = new File([blob], `voice-instruction.${extension}`, { type });
                    const transfer = new DataTransfer();

                    transfer.items.add(file);
                    input.files = transfer.files;
                    preview.src = URL.createObjectURL(blob);
                    preview.classList.remove('hidden');
                    status.textContent = `Rekaman siap diproses (${Math.max(1, Math.round(blob.size / 1024))} KB).`;

                    stream?.getTracks().forEach((track) => track.stop());
                    stream = null;
                });

                recorder.start();
                startButton.disabled = true;
                stopButton.disabled = false;
                status.textContent = 'Sedang merekam...';
                showSnackbar('Sedang merekam suara...', 'loading', false);
            } catch (error) {
                status.textContent = 'Tidak bisa mengakses mikrofon. Cek izin browser.';
                showSnackbar('Tidak bisa mengakses mikrofon. Cek izin browser.', 'danger');
            }
        });

        stopButton.addEventListener('click', () => {
            if (recorder?.state === 'recording') {
                recorder.stop();
            }

            startButton.disabled = false;
            stopButton.disabled = true;
            showSnackbar('Rekaman selesai. Klik Proses Suara.', 'success');
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    applyTheme(preferredTheme());
    bindSnackbars();
    bindVoiceRecorder();

    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.addEventListener('click', () => applyTheme(button.dataset.themeOption));
    });
});
