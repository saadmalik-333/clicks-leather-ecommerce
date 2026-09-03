<?php
/**
 * Clicks Leather — Chatbot Widget
 * Floating keyword-based FAQ chatbot
 */
?>
<style>
    .chatbot-bubble {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: var(--color-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(140, 92, 56, 0.4);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        z-index: 1002;
        color: white;
    }

    .chatbot-bubble:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(140, 92, 56, 0.5);
    }

    .chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 30px;
        width: 350px;
        max-width: calc(100vw - 60px);
        height: 450px;
        max-height: calc(100vh - 150px);
        background: var(--bg-card);
        border-radius: var(--radius-md);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        z-index: 1002;
        opacity: 0;
        visibility: hidden;
        transform: translateY(20px);
        transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
    }

    .chatbot-window.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .chatbot-header {
        background: var(--color-primary);
        color: white;
        padding: 1rem 1.25rem;
        border-radius: var(--radius-md) var(--radius-md) 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-shrink: 0;
    }

    .chatbot-header h3 {
        font-family: var(--font-display);
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: 0.02em;
        color: white;
    }

    .chatbot-close {
        background: transparent;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 4px;
        transition: background 0.2s ease;
    }

    .chatbot-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        background: #FDF7F0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .chatbot-message {
        max-width: 85%;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        font-size: 0.9rem;
        line-height: 1.5;
        word-wrap: break-word;
    }

    .chatbot-message.bot {
        background: #E8E0D5;
        color: var(--text-primary);
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }

    .chatbot-message.user {
        background: var(--color-primary);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }

    .chatbot-link {
        color: var(--color-primary);
        text-decoration: none;
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .chatbot-link:hover {
        color: #6b4423;
        text-decoration: underline;
    }

    .chatbot-input-area {
        padding: 1rem;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 0.5rem;
        background: var(--bg-card);
        border-radius: 0 0 var(--radius-md) var(--radius-md);
        flex-shrink: 0;
    }

    .chatbot-input {
        flex: 1;
        min-width: 0;
        padding: 0.75rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-sm);
        font-family: var(--font-body);
        font-size: 0.9rem;
        color: var(--text-primary);
        background: white;
        outline: none;
        transition: border-color 0.2s ease;
    }

    .chatbot-input:focus {
        border-color: var(--color-primary);
    }

    .chatbot-send {
        background: var(--color-primary);
        border: none;
        color: white;
        width: 44px;
        height: 44px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, transform 0.1s ease;
        flex-shrink: 0;
    }

    .chatbot-send:hover {
        background: #6b4423;
    }

    .chatbot-send:active {
        transform: scale(0.95);
    }

    @media (max-width: 480px) {
        .chatbot-bubble {
            bottom: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
        }

        .chatbot-window {
            bottom: 80px;
            right: 20px;
            width: calc(100vw - 40px);
            height: calc(100dvh - 120px);
        }
    }
</style>

<!-- Chatbot Widget -->
<div id="chatbot-bubble" class="chatbot-bubble">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"></path>
    </svg>
</div>

<div id="chatbot-window" class="chatbot-window">
    <div class="chatbot-header">
        <h3>Clicks Leather Assistant</h3>
        <button id="chatbot-close" class="chatbot-close" aria-label="Close chat">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>
    <div id="chatbot-messages" class="chatbot-messages"></div>
    <div class="chatbot-input-area">
        <input type="text" id="chatbot-input" class="chatbot-input" placeholder="Ask me anything..." autocomplete="off">
        <button id="chatbot-send" class="chatbot-send" aria-label="Send message">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="22" y1="2" x2="11" y2="13"></line>
                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
            </svg>
        </button>
    </div>
</div>

<script>const CHATBOT_BASE_URL = '<?= PUBLIC_URL ?>';</script>
<script src="<?= PUBLIC_URL ?>/js/chatbot.js?v=<?= filemtime(__DIR__ . '/../js/chatbot.js') ?>"></script>
