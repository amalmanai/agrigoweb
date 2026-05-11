document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('voice-assistant-btn');
    const statusBox = document.getElementById('voice-status');
    const statusText = document.getElementById('voice-status-text');

    if (!btn) return;

    // Check browser support
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    
    if (!SpeechRecognition) {
        btn.style.display = 'none';
        console.warn('Speech Recognition API not supported in this browser.');
        return;
    }

    const recognition = new SpeechRecognition();
    recognition.lang = 'fr-FR';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    let isRecording = false;

    btn.addEventListener('click', () => {
        if (isRecording) {
            recognition.stop();
        } else {
            recognition.start();
        }
    });

    recognition.onstart = function() {
        isRecording = true;
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-danger');
        btn.style.transform = 'scale(1.1)';
        statusBox.style.display = 'block';
        statusText.innerText = 'Écoute en cours... Parlez maintenant.';
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        statusText.innerText = 'Traitement : "' + transcript + '"...';
        
        // Send to backend
        fetch('/api/voice/parse', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ text: transcript })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusText.innerText = data.message;
                // Optional: show a toast notification
                setTimeout(() => {
                    statusBox.style.display = 'none';
                }, 3000);
            } else {
                statusText.innerText = 'Erreur: ' + data.message;
                setTimeout(() => {
                    statusBox.style.display = 'none';
                }, 4000);
            }
        })
        .catch(err => {
            console.error('Error sending voice command:', err);
            statusText.innerText = 'Erreur de connexion.';
            setTimeout(() => {
                statusBox.style.display = 'none';
            }, 3000);
        });
    };

    recognition.onspeechend = function() {
        recognition.stop();
    };

    recognition.onend = function() {
        isRecording = false;
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-primary');
        btn.style.transform = 'scale(1)';
        if (statusText.innerText === 'Écoute en cours... Parlez maintenant.') {
            statusBox.style.display = 'none';
        }
    };

    recognition.onerror = function(event) {
        console.error('Speech recognition error', event.error);
        statusText.innerText = 'Erreur: ' + event.error;
        setTimeout(() => {
            statusBox.style.display = 'none';
        }, 3000);
    };
});
