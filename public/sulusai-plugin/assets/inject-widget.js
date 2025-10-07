document.addEventListener('DOMContentLoaded', function () {
    // Load the widget.js script from SulusAI
    const widgetScript = document.createElement('script');
    widgetScript.src = 'https://sulusai.com/widget.js';
    widgetScript.type = 'module';
    document.body.appendChild(widgetScript);

    // Create and append the <sulus-widget> tag
    const widgetTag = document.createElement('sulus-widget');

    widgetTag.setAttribute('public-key', '38bca0b8-91cf-4f32-a422-fc9e77bbddb9');
    widgetTag.setAttribute('assistant-id', 'ab1aebb9-cdaa-40f9-9eb4-320b290ab96e');
    widgetTag.setAttribute('mode', 'voice');
    widgetTag.setAttribute('theme', 'dark');
    widgetTag.setAttribute('base-bg-color', '#000000');
    widgetTag.setAttribute('accent-color', '#14B8A6');
    widgetTag.setAttribute('cta-button-color', '#000000');
    widgetTag.setAttribute('cta-button-text-color', '#FFFFFF');
    widgetTag.setAttribute('border-radius', 'large');
    widgetTag.setAttribute('size', 'full');
    widgetTag.setAttribute('position', 'bottom-right');
    widgetTag.setAttribute('title', 'TALK WITH NORA');
    widgetTag.setAttribute('start-button-text', 'Talk!');
    widgetTag.setAttribute('end-button-text', 'End');
    widgetTag.setAttribute('cta-title', "LET'S TALK");
    widgetTag.setAttribute('cta-subtitle', 'Need Help?');
    widgetTag.setAttribute('chat-first-message', 'Hey, How can I help you today?');
    widgetTag.setAttribute('chat-placeholder', 'Type your message...');
    widgetTag.setAttribute('voice-show-transcript', 'false');
    widgetTag.setAttribute('consent-required', 'true');
    widgetTag.setAttribute('consent-title', 'Terms and conditions');
    widgetTag.setAttribute('consent-content', 'By clicking "Agree," and each time I interact with this AI agent, I consent to the recording, storage, and sharing of my communications with third-party service providers, and as otherwise described in our Terms of Service.');
    widgetTag.setAttribute('consent-storage-key', 'nora_widget_consent');

    document.body.appendChild(widgetTag);
});
