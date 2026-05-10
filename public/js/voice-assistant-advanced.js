/**
 * 🎤🧠 AgriGo Voice Task AI - Advanced Voice Assistant
 * 
 * Interface moderne avec:
 * - Visualisation audio (waveform)
 * - Suggestions contextuelles
 * - Historique des commandes
 * - Feedback visuel enrichi
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        lang: 'fr-FR',
        apiEndpoint: '/api/voice/parse',
        animationDuration: 300,
        waveformBars: 40,
        autoCloseDelay: 4000
    };

    // State
    let state = {
        isRecording: false,
        recognition: null,
        audioContext: null,
        analyser: null,
        microphone: null,
        animationId: null,
        commandHistory: [],
        currentSuggestions: []
    };

    // DOM Elements (initialized on load)
    let elements = {};

    /**
     * Initialize the voice assistant
     */
    function init() {
        // Initialize DOM elements
        initElements();

        // Check browser support
        if (!checkBrowserSupport()) {
            console.warn('Voice Assistant: Browser not supported');
            
            // Still add click listener to show error
            if (elements.btn) {
                elements.btn.addEventListener('click', () => {
                    alert('Désolé, votre navigateur ne supporte pas la reconnaissance vocale ou l\'accès au microphone est bloqué. Veuillez utiliser Google Chrome ou Edge, ou vérifier vos permissions.');
                });
            }
            return;
        }

        // Setup event listeners
        setupEventListeners();

        // Initialize speech recognition
        initSpeechRecognition();

        // Load suggestions for current page
        loadSuggestions();

        console.log('🎤 AgriGo Voice Task AI initialized');
    }

    /**
     * Check if browser supports required APIs
     */
    function checkBrowserSupport() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        return !!SpeechRecognition && !!navigator.mediaDevices?.getUserMedia;
    }

    /**
     * Initialize DOM element references
     */
    function initElements() {
        elements = {
            container: document.getElementById('voice-assistant-container'),
            btn: document.getElementById('voice-assistant-btn'),
            statusBox: document.getElementById('voice-status'),
            statusText: document.getElementById('voice-status-text'),
            waveform: document.getElementById('voice-waveform'),
            suggestions: document.getElementById('voice-suggestions'),
            history: document.getElementById('voice-history'),
            closeBtn: document.getElementById('voice-close'),
            helpBtn: document.getElementById('voice-help'),
            panel: document.getElementById('voice-panel')
        };
    }

    /**
     * Setup event listeners
     */
    function setupEventListeners() {
        if (!elements.btn) return;

        // Main button click
        elements.btn.addEventListener('click', toggleRecording);

        // Close button
        if (elements.closeBtn) {
            elements.closeBtn.addEventListener('click', hidePanel);
        }

        // Help button
        if (elements.helpBtn) {
            elements.helpBtn.addEventListener('click', showHelp);
        }

        // Keyboard shortcut (Space when panel is open)
        document.addEventListener('keydown', (e) => {
            if (e.code === 'Space' && state.isRecording) {
                e.preventDefault();
                stopRecording();
            }
        });

        // Close on click outside
        document.addEventListener('click', (e) => {
            if (elements.panel && 
                !elements.panel.contains(e.target) && 
                !elements.btn.contains(e.target) &&
                elements.panel.classList.contains('active')) {
                hidePanel();
            }
        });
    }

    /**
     * Initialize Web Speech API
     */
    function initSpeechRecognition() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        state.recognition = new SpeechRecognition();
        state.recognition.lang = CONFIG.lang;
        state.recognition.interimResults = true;
        state.recognition.maxAlternatives = 1;
        state.recognition.continuous = false;

        state.recognition.onstart = onRecordingStart;
        state.recognition.onresult = onRecordingResult;
        state.recognition.onerror = onRecordingError;
        state.recognition.onend = onRecordingEnd;
    }

    /**
     * Toggle recording state
     */
    function toggleRecording() {
        if (state.isRecording) {
            stopRecording();
        } else {
            startRecording();
        }
    }

    /**
     * Start voice recording
     */
    async function startRecording() {
        try {
            // Show panel
            showPanel();

            // Request microphone access for waveform
            await initAudioVisualization();

            // Start speech recognition
            state.recognition.start();

        } catch (error) {
            console.error('Failed to start recording:', error);
            showError('Erreur: Impossible d\'accéder au microphone');
        }
    }

    /**
     * Stop voice recording
     */
    function stopRecording() {
        if (state.recognition) {
            state.recognition.stop();
        }
        stopAudioVisualization();
    }

    /**
     * Initialize audio visualization
     */
    async function initAudioVisualization() {
        try {
            state.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            state.microphone = state.audioContext.createMediaStreamSource(stream);
            state.analyser = state.audioContext.createAnalyser();
            state.analyser.fftSize = 256;
            
            state.microphone.connect(state.analyser);
            
            // Start visualization
            animateWaveform();

        } catch (error) {
            console.warn('Audio visualization not available:', error);
        }
    }

    /**
     * Stop audio visualization
     */
    function stopAudioVisualization() {
        if (state.animationId) {
            cancelAnimationFrame(state.animationId);
            state.animationId = null;
        }

        if (state.audioContext) {
            state.audioContext.close();
            state.audioContext = null;
        }

        state.analyser = null;
        state.microphone = null;
    }

    /**
     * Animate the waveform
     */
    function animateWaveform() {
        if (!state.analyser || !elements.waveform) return;

        const dataArray = new Uint8Array(state.analyser.frequencyBinCount);
        const bars = elements.waveform.querySelectorAll('.waveform-bar');

        function update() {
            state.analyser.getByteFrequencyData(dataArray);

            // Update each bar
            bars.forEach((bar, index) => {
                const dataIndex = Math.floor(index * dataArray.length / bars.length);
                const value = dataArray[dataIndex] || 0;
                const height = Math.max(4, (value / 255) * 60);
                
                bar.style.height = `${height}px`;
                
                // Color based on intensity
                if (value > 200) {
                    bar.style.backgroundColor = '#ef4444'; // Red (loud)
                } else if (value > 100) {
                    bar.style.backgroundColor = '#22c55e'; // Green (normal)
                } else {
                    bar.style.backgroundColor = '#3b82f6'; // Blue (quiet)
                }
            });

            state.animationId = requestAnimationFrame(update);
        }

        update();
    }

    /**
     * Recording started
     */
    function onRecordingStart() {
        state.isRecording = true;
        
        // Update UI
        elements.btn.classList.add('recording');
        elements.statusBox.classList.add('active');
        elements.statusText.textContent = '🎤 Écoute en cours... Parlez maintenant';
        
        if (elements.waveform) {
            elements.waveform.classList.add('active');
        }

        // Show hint
        showHint('Dites une commande comme: "Créer une tâche arroser les tomates"');
    }

    /**
     * Recording result received
     */
    function onRecordingResult(event) {
        const transcript = Array.from(event.results)
            .map(result => result[0].transcript)
            .join('');

        const isFinal = event.results[0].isFinal;

        if (isFinal) {
            elements.statusText.textContent = `🧠 Traitement: "${transcript}"...`;
            stopRecording();
            processCommand(transcript);
        } else {
            elements.statusText.textContent = `📝 ${transcript}`;
        }
    }

    /**
     * Recording error
     */
    function onRecordingError(event) {
        console.error('Speech recognition error:', event.error);
        
        let message = 'Erreur de reconnaissance vocale';
        
        switch (event.error) {
            case 'no-speech':
                message = 'Aucune parole détectée. Réessayez.';
                break;
            case 'audio-capture':
                message = 'Microphone non trouvé.';
                break;
            case 'not-allowed':
                message = 'Permission microphone refusée.';
                break;
            case 'network':
                message = 'Erreur réseau. Vérifiez votre connexion.';
                break;
        }

        showError(message);
        resetUI();
    }

    /**
     * Recording ended
     */
    function onRecordingEnd() {
        state.isRecording = false;
        resetUI();
    }

    /**
     * Reset UI to default state
     */
    function resetUI() {
        elements.btn.classList.remove('recording');
        elements.statusBox.classList.remove('active');
        
        if (elements.waveform) {
            elements.waveform.classList.remove('active');
        }
    }

    /**
     * Process voice command via API
     */
    async function processCommand(transcript) {
        try {
            const response = await fetch(CONFIG.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ text: transcript })
            });

            const data = await response.json();

            // Add to history
            addToHistory(transcript, data);

            if (data.success) {
                showSuccess(data.message);
                
                // Handle navigation
                if (data.data?.redirect) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1500);
                }
            } else {
                showError(data.message || 'Commande non reconnue');
                
                // Show suggestions if available
                if (data.suggestions) {
                    updateSuggestions(data.suggestions);
                }
            }

        } catch (error) {
            console.error('API error:', error);
            showError('Erreur de connexion au serveur');
        }
    }

    /**
     * Add command to history
     */
    function addToHistory(command, result) {
        const historyItem = {
            timestamp: new Date().toISOString(),
            command,
            success: result.success,
            message: result.message,
            action: result.action
        };

        state.commandHistory.unshift(historyItem);
        if (state.commandHistory.length > 10) {
            state.commandHistory.pop();
        }

        updateHistoryUI();
    }

    /**
     * Update history UI
     */
    function updateHistoryUI() {
        if (!elements.history) return;

        const historyHTML = state.commandHistory.map(item => `
            <div class="voice-history-item ${item.success ? 'success' : 'error'}">
                <span class="voice-history-icon">${item.success ? '✅' : '❌'}</span>
                <span class="voice-history-text">${escapeHtml(item.command)}</span>
                <span class="voice-history-message">${escapeHtml(item.message)}</span>
            </div>
        `).join('');

        elements.history.innerHTML = historyHTML || '<p class="voice-empty">Aucune commande récente</p>';
    }

    /**
     * Load context-aware suggestions (local suggestions without Google)
     */
    async function loadSuggestions() {
        try {
            // Get current page from URL
            const path = window.location.pathname;
            let page = 'dashboard';
            
            if (path.includes('culture')) page = 'cultures';
            else if (path.includes('tache')) page = 'tasks';
            else if (path.includes('produit') || path.includes('stock')) page = 'stock';
            else if (path.includes('vente')) page = 'sales';
            else if (path.includes('parcelle')) page = 'parcelles';

            // Use local suggestions instead of Google API
            const localSuggestions = getLocalSuggestions(page);
            updateSuggestions(localSuggestions);

        } catch (error) {
            console.warn('Failed to load suggestions:', error);
        }
    }

    /**
     * Get local suggestions based on current page
     */
    function getLocalSuggestions(page) {
        const suggestions = {
            'dashboard': [
                'Créer une tâche',
                'Lister mes cultures',
                'Vérifier le stock',
                'Voir les ventes récentes',
                'Météo aujourd\'hui'
            ],
            'cultures': [
                'Nouvelle culture de tomates',
                'Lister toutes mes cultures',
                'État des cultures',
                'Arroser les cultures'
            ],
            'tasks': [
                'Créer une tâche arroser',
                'Lister mes tâches',
                'Terminer la tâche 1',
                'Tâches en attente'
            ],
            'stock': [
                'Ajouter du stock',
                'Vérifier le stock',
                'Alertes de stock',
                'Produits disponibles'
            ],
            'sales': [
                'Nouvelle vente',
                'Lister les ventes',
                'Statistiques des ventes',
                'Ventes récentes'
            ],
            'parcelles': [
                'Créer une parcelle',
                'Lister mes parcelles',
                'État des parcelles',
                'Météo des parcelles'
            ]
        };

        return suggestions[page] || suggestions['dashboard'];
    }

    /**
     * Update suggestions UI
     */
    function updateSuggestions(suggestions) {
        if (!elements.suggestions) return;

        state.currentSuggestions = suggestions;

        const suggestionsHTML = suggestions.map(s => `
            <button class="voice-suggestion-btn" data-command="${escapeHtml(s)}">
                💡 ${escapeHtml(s)}
            </button>
        `).join('');

        elements.suggestions.innerHTML = `
            <div class="voice-suggestions-title">Suggestions:</div>
            ${suggestionsHTML}
        `;

        // Add click handlers
        elements.suggestions.querySelectorAll('.voice-suggestion-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const command = btn.dataset.command;
                processCommand(command);
            });
        });
    }

    /**
     * Show success message
     */
    function showSuccess(message) {
        showStatus(message, 'success');
    }

    /**
     * Show error message
     */
    function showError(message) {
        showStatus(message, 'error');
    }

    /**
     * Show hint message
     */
    function showHint(message) {
        showStatus(message, 'hint');
    }

    /**
     * Show status message
     */
    function showStatus(message, type) {
        if (!elements.statusText) return;

        elements.statusText.textContent = message;
        elements.statusText.className = `voice-status-text ${type}`;

        // Auto-hide after delay for success/error
        if (type === 'success' || type === 'error') {
            setTimeout(() => {
                if (!state.isRecording) {
                    hidePanel();
                }
            }, CONFIG.autoCloseDelay);
        }
    }

    /**
     * Show the voice panel
     */
    function showPanel() {
        if (elements.panel) {
            elements.panel.classList.add('active');
        }
    }

    /**
     * Hide the voice panel
     */
    function hidePanel() {
        if (elements.panel) {
            elements.panel.classList.remove('active');
        }
        stopRecording();
    }

    /**
     * Show help modal
     */
    function showHelp() {
        const helpContent = `
            <div class="voice-help-modal">
                <h3>🎤 Commandes Vocales</h3>
                <div class="voice-help-section">
                    <h4>📋 Tâches</h4>
                    <ul>
                        <li>"Créer une tâche [description]"</li>
                        <li>"Lister mes tâches"</li>
                        <li>"Terminer la tâche 5"</li>
                    </ul>
                </div>
                <div class="voice-help-section">
                    <h4>🌱 Cultures</h4>
                    <ul>
                        <li>"Nouvelle culture blé dans parcelle 3"</li>
                        <li>"Lister mes cultures"</li>
                    </ul>
                </div>
                <div class="voice-help-section">
                    <h4>📦 Stock</h4>
                    <ul>
                        <li>"Ajouter 50 kg d'engrais"</li>
                        <li>"Stock de tomates"</li>
                        <li>"Alertes stock"</li>
                    </ul>
                </div>
                <div class="voice-help-section">
                    <h4>🧭 Navigation</h4>
                    <ul>
                        <li>"Aller à l'administration"</li>
                        <li>"Ajouter un utilisateur"</li>
                        <li>"Aller aux cultures"</li>
                        <li>"Ouvrir stock"</li>
                        <li>"Météo"</li>
                    </ul>
                </div>
                <button class="voice-help-close" onclick="this.closest('.voice-help-modal').remove()">Fermer</button>
            </div>
        `;

        const modal = document.createElement('div');
        modal.className = 'voice-help-overlay';
        modal.innerHTML = helpContent;
        document.body.appendChild(modal);

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
