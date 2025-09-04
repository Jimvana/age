// Settings UI Components for Face Detection Camera

class SettingsUI {
    constructor() {
        this.isVisible = false;
        this.container = null;
        this.createSettingsPanel();
        this.attachEventListeners();
    }

    createSettingsPanel() {
        // Create main settings container
        this.container = document.createElement('div');
        this.container.id = 'settings-panel';
        this.container.innerHTML = `
            <div class="settings-overlay">
                <div class="settings-content">
                    <div class="settings-header">
                        <h2>Face Detection Settings</h2>
                        <button class="close-btn" id="close-settings">&times;</button>
                    </div>

                    <div class="settings-body">
                        <div class="settings-section">
                            <h3>Face Detection</h3>
                            <div class="setting-group">
                                <label for="minFaceSize">Min Face Size (px):</label>
                                <input type="number" id="minFaceSize" min="20" max="500" value="${CONFIG.faceDetection.minFaceSize}">
                            </div>
                            <div class="setting-group">
                                <label for="maxFaceSize">Max Face Size (px):</label>
                                <input type="number" id="maxFaceSize" min="50" max="1000" value="${CONFIG.faceDetection.maxFaceSize}">
                            </div>
                            <div class="setting-group">
                                <label for="scoreThreshold">Detection Confidence:</label>
                                <input type="range" id="scoreThreshold" min="0.1" max="1.0" step="0.1" value="${CONFIG.faceDetection.scoreThreshold}">
                                <span id="scoreThresholdValue">${CONFIG.faceDetection.scoreThreshold}</span>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3>Age & Gender</h3>
                            <div class="setting-group">
                                <label for="ageThreshold">Age Threshold:</label>
                                <input type="number" id="ageThreshold" min="0" max="120" value="${CONFIG.ageGender.ageThreshold}">
                            </div>
                            <div class="setting-group">
                                <label>
                                    <input type="checkbox" id="showAge" ${CONFIG.ageGender.showAge ? 'checked' : ''}>
                                    Show Age
                                </label>
                            </div>
                            <div class="setting-group">
                                <label>
                                    <input type="checkbox" id="showGender" ${CONFIG.ageGender.showGender ? 'checked' : ''}>
                                    Show Gender
                                </label>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3>Visual Settings</h3>
                            <div class="setting-group">
                                <label>
                                    <input type="checkbox" id="showDetectionZone" ${CONFIG.ui.showDetectionZone ? 'checked' : ''}>
                                    Show Detection Zone
                                </label>
                            </div>
                            <div class="setting-group">
                                <label for="textSize">Text Size:</label>
                                <input type="number" id="textSize" min="8" max="32" value="${CONFIG.ui.textSize}">
                            </div>
                        </div>

                        <div class="settings-section">
                            <h3>Camera Settings</h3>
                            <div class="setting-group">
                                <label for="cameraFacing">Camera:</label>
                                <select id="cameraFacing">
                                    <option value="user" ${CONFIG.camera.facingMode === 'user' ? 'selected' : ''}>Front Camera</option>
                                    <option value="environment" ${CONFIG.camera.facingMode === 'environment' ? 'selected' : ''}>Back Camera</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="settings-footer">
                        <button id="save-settings" class="primary-btn">Save Settings</button>
                        <button id="reset-settings" class="secondary-btn">Reset to Defaults</button>
                        <button id="export-settings" class="secondary-btn">Export</button>
                        <button id="import-settings" class="secondary-btn">Import</button>
                    </div>
                </div>
            </div>
        `;

        // Add CSS styles
        const style = document.createElement('style');
        style.textContent = `
            #settings-panel {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                z-index: 10000;
                display: none;
            }

            .settings-overlay {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .settings-content {
                background: white;
                border-radius: 10px;
                max-width: 500px;
                max-height: 80vh;
                overflow-y: auto;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            }

            .settings-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 20px;
                border-bottom: 1px solid #eee;
            }

            .settings-header h2 {
                margin: 0;
                color: #333;
            }

            .close-btn {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
            }

            .settings-body {
                padding: 20px;
            }

            .settings-section {
                margin-bottom: 30px;
            }

            .settings-section h3 {
                margin: 0 0 15px 0;
                color: #555;
                font-size: 16px;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }

            .setting-group {
                margin-bottom: 15px;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .setting-group label {
                flex: 1;
                font-weight: 500;
                color: #333;
            }

            .setting-group input[type="number"],
            .setting-group input[type="range"],
            .setting-group select {
                flex: 0 0 100px;
                padding: 5px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .setting-group input[type="checkbox"] {
                width: auto;
                margin-right: 5px;
            }

            .settings-footer {
                padding: 20px;
                border-top: 1px solid #eee;
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }

            .primary-btn {
                background: #007bff;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
                font-weight: 500;
            }

            .secondary-btn {
                background: #6c757d;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 5px;
                cursor: pointer;
            }

            .primary-btn:hover {
                background: #0056b3;
            }

            .secondary-btn:hover {
                background: #545b62;
            }

            @media (max-width: 600px) {
                .settings-content {
                    margin: 10px;
                    max-width: none;
                    max-height: none;
                }

                .settings-footer {
                    flex-direction: column;
                }

                .setting-group {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 5px;
                }
            }
        `;

        document.head.appendChild(style);
        document.body.appendChild(this.container);
    }

    attachEventListeners() {
        // Close button
        document.getElementById('close-settings').addEventListener('click', () => {
            this.hide();
        });

        // Click outside to close
        this.container.addEventListener('click', (e) => {
            if (e.target === this.container) {
                this.hide();
            }
        });

        // ESC key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isVisible) {
                this.hide();
            }
        });

        // Settings key (S key) to toggle
        document.addEventListener('keydown', (e) => {
            if (e.key === 's' || e.key === 'S') {
                // Only if not typing in an input
                if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    this.toggle();
                }
            }
        });

        // Update range value display
        const scoreThreshold = document.getElementById('scoreThreshold');
        const scoreThresholdValue = document.getElementById('scoreThresholdValue');
        scoreThreshold.addEventListener('input', () => {
            scoreThresholdValue.textContent = scoreThreshold.value;
        });

        // Save settings
        document.getElementById('save-settings').addEventListener('click', () => {
            this.saveSettings();
        });

        // Reset settings
        document.getElementById('reset-settings').addEventListener('click', () => {
            if (confirm('Reset all settings to defaults?')) {
                resetSettings();
            }
        });

        // Export settings
        document.getElementById('export-settings').addEventListener('click', () => {
            this.exportSettings();
        });

        // Import settings
        document.getElementById('import-settings').addEventListener('click', () => {
            this.importSettings();
        });
    }

    show() {
        this.container.style.display = 'block';
        this.isVisible = true;
    }

    hide() {
        this.container.style.display = 'none';
        this.isVisible = false;
    }

    toggle() {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show();
        }
    }

    saveSettings() {
        try {
            // Update CONFIG object with form values
            CONFIG.faceDetection.minFaceSize = parseInt(document.getElementById('minFaceSize').value);
            CONFIG.faceDetection.maxFaceSize = parseInt(document.getElementById('maxFaceSize').value);
            CONFIG.faceDetection.scoreThreshold = parseFloat(document.getElementById('scoreThreshold').value);

            CONFIG.ageGender.ageThreshold = parseInt(document.getElementById('ageThreshold').value);
            CONFIG.ageGender.showAge = document.getElementById('showAge').checked;
            CONFIG.ageGender.showGender = document.getElementById('showGender').checked;

            CONFIG.ui.showDetectionZone = document.getElementById('showDetectionZone').checked;
            CONFIG.ui.textSize = parseInt(document.getElementById('textSize').value);

            CONFIG.camera.facingMode = document.getElementById('cameraFacing').value;

            // Validate and save
            if (validateConfig()) {
                saveSettings();
                alert('Settings saved successfully!');
                this.hide();

                // Trigger app restart if needed
                if (window.restartDetection) {
                    window.restartDetection();
                }
            } else {
                alert('Invalid settings. Please check your values.');
            }
        } catch (error) {
            console.error('Error saving settings:', error);
            alert('Error saving settings. Check console for details.');
        }
    }

    exportSettings() {
        try {
            const settingsJson = JSON.stringify(CONFIG, null, 2);
            const blob = new Blob([settingsJson], { type: 'application/json' });
            const url = URL.createObjectURL(blob);

            const a = document.createElement('a');
            a.href = url;
            a.download = 'face-detection-settings.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Error exporting settings:', error);
            alert('Error exporting settings.');
        }
    }

    importSettings() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = '.json';

        input.onchange = (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                    try {
                        const importedSettings = JSON.parse(event.target.result);

                        // Validate imported settings structure
                        if (importedSettings && typeof importedSettings === 'object') {
                            // Update CONFIG with imported values
                            Object.keys(importedSettings).forEach(category => {
                                if (CONFIG[category] && typeof importedSettings[category] === 'object') {
                                    Object.assign(CONFIG[category], importedSettings[category]);
                                }
                            });

                            if (validateConfig()) {
                                saveSettings();
                                alert('Settings imported successfully!');
                                this.hide();

                                // Reload to apply new settings
                                location.reload();
                            } else {
                                alert('Invalid settings file. Please check the format.');
                            }
                        } else {
                            alert('Invalid settings file format.');
                        }
                    } catch (error) {
                        console.error('Error parsing settings file:', error);
                        alert('Error reading settings file.');
                    }
                };
                reader.readAsText(file);
            }
        };

        input.click();
    }
}

// Initialize settings UI when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.settingsUI = new SettingsUI();

    // Add settings button to page
    const settingsBtn = document.createElement('button');
    settingsBtn.id = 'settings-toggle-btn';
    settingsBtn.innerHTML = '⚙️ Settings';
    settingsBtn.style.cssText = `
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 9999;
        padding: 10px 15px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
    `;

    settingsBtn.addEventListener('click', () => {
        window.settingsUI.toggle();
    });

    document.body.appendChild(settingsBtn);
});
