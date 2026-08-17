/**
 * Clicks Leather — Chatbot JavaScript
 * Keyword-based FAQ chatbot with dynamic shipping cost lookup
 */

(function() {
    'use strict';

    // Chatbot topics with keyword matching
    const topics = [
        {
            keywords: ['hi', 'hello', 'hey', 'hi there', 'good morning', 'good afternoon', 'good evening'],
            answer: 'Hello! How can I help you today? Ask me about shipping, returns, warranty, or anything else.',
            type: 'static'
        },
        {
            keywords: ['material', 'leather', 'real leather', 'genuine leather', 'pu leather', 'synthetic', 'fake leather', 'quality', 'what is it made of', 'leather type', 'made of', 'material quality', 'authentic', 'genuine'],
            answer: 'We use 100% real leather for all our products. We never use PU (synthetic) leather — only genuine, high-quality leather that ages beautifully over time.',
            type: 'static'
        },
        {
            keywords: ['made to order', 'made-to-order', 'custom made', 'handmade', 'handcrafted', 'production time', 'how long to make', 'how long does it take', 'manufacturing', 'crafting', 'production', 'make time'],
            answer: 'Yes, each piece is handcrafted and made to order. This ensures every item meets our quality standards and is crafted specifically for you.',
            type: 'static'
        },
        {
            keywords: ['customize', 'customization', 'engraving', 'personalize', 'personalization', 'laser', 'custom', 'engrave', 'personalised', 'personalized', 'add name', 'add text', 'custom design'],
            answer: 'Yes, we offer laser engraving customization for personalization. Contact us before placing your order to discuss customization options.',
            type: 'static'
        },
        {
            keywords: ['return', 'refund', 'return policy', 'money back', 'send back', 'can i return', 'returning', 'refund policy', 'get refund', 'return item', 'send item back'],
            answer: 'We accept returns within 14 days for damaged items or if you receive the wrong size. Please contact us immediately if you have any issues with your order.',
            type: 'static'
        },
        {
            keywords: ['change mind', 'changed my mind', 'dont like', 'not what i wanted', 'just because', 'no longer want', 'dont want anymore', 'decided against', 'not interested'],
            answer: 'No, made-to-order items cannot be returned once production has started. Since each piece is custom-made specifically for you, we cannot accept returns for change of mind.',
            type: 'static'
        },
        {
            keywords: ['fit', 'size', 'doesnt fit', 'too big', 'too small', 'wrong size', 'replacement', 'size issue', 'fitting', 'does not fit', 'size problem', 'exchange size'],
            answer: 'We offer a free replacement for fit issues. If your item doesn\'t fit properly, contact us and we\'ll arrange a replacement at no additional cost to you.',
            type: 'static'
        },
        {
            keywords: ['shipping time', 'delivery time', 'how long', 'when will i get', 'delivery', 'timeline', 'days', 'when will it arrive', 'when will i receive', 'get my order', 'arrive', 'receive', 'how many days', 'delivery date', 'shipping duration', 'how long shipping', 'shipping days', 'delivery days', 'weeks', 'shipping policy', 'shipping information', 'about shipping', 'tell me about shipping'],
            answer: 'Total delivery time is 14-15 days. This includes 4-6 days for manufacturing/handcrafting your order, plus 8-10 days for international shipping to your location. We offer worldwide shipping to most countries.',
            type: 'static'
        },
        {
            keywords: ['worldwide', 'international', 'ship to', 'country', 'do you ship', 'where do you ship', 'shipping countries', 'global shipping', 'international shipping', 'ship worldwide', 'deliver to', 'can you ship to'],
            answer: 'Yes, we offer worldwide shipping to most countries. Shipping costs and delivery times may vary depending on your location.',
            type: 'static'
        },
        {
            keywords: ['shipping cost', 'shipping price', 'how much shipping', 'shipping fee', 'delivery cost', 'charge', 'shipping rate', 'how much to ship'],
            answer: 'dynamic_shipping',
            type: 'dynamic'
        },
        {
            keywords: ['facebook', 'fb', 'on facebook'],
            answer: '<a href="https://www.facebook.com/share/1JdmTwcNar/" target="_blank" rel="noopener" class="chatbot-link">Follow us on Facebook</a>',
            type: 'static'
        },
        {
            keywords: ['instagram', 'insta', 'ig', 'on instagram', 'on insta'],
            answer: '<a href="https://www.instagram.com/clicks_leather?igsh=YmkxNTJqMXlsOXB0" target="_blank" rel="noopener" class="chatbot-link">Follow us on Instagram</a>',
            type: 'static'
        },
        {
            keywords: ['whatsapp', 'whatsapp number', 'message on whatsapp'],
            answer: '<a href="https://wa.me/923063888988" target="_blank" rel="noopener" class="chatbot-link">Message us on WhatsApp</a>',
            type: 'static'
        },
        {
            keywords: ['social media', 'social', 'socials', 'follow you', 'social network', 'on social'],
            answer: 'Find us on social media: <a href="https://www.facebook.com/share/1JdmTwcNar/" target="_blank" rel="noopener" class="chatbot-link">Facebook</a>, <a href="https://www.instagram.com/clicks_leather?igsh=YmkxNTJqMXlsOXB0" target="_blank" rel="noopener" class="chatbot-link">Instagram</a>, <a href="https://wa.me/923063888988" target="_blank" rel="noopener" class="chatbot-link">WhatsApp</a>',
            type: 'static'
        },
        {
            keywords: ['most popular', 'best seller', 'bestseller', 'popular items', 'top products', 'trending'],
            answer: 'Check out our most popular picks on the homepage! <a href="' + CHATBOT_BASE_URL + '/index.php" target="_blank" rel="noopener" class="chatbot-link">View Most Popular Items</a>',
            type: 'static'
        },
        {
            keywords: ['how to order', 'how do i order', 'how to buy', 'place an order', 'how does ordering work'],
            answer: 'To place an order: browse our products, add items to your cart, go to checkout, fill in your shipping details, and complete your order. It\'s that simple!',
            type: 'static'
        },
        {
            keywords: ['track my order', 'track order', 'where is my order', 'order status', 'check my order', 'order tracking'],
            answer: 'You can check your order status anytime under \'My Orders\' in your account. <a href="' + CHATBOT_BASE_URL + '/account.php#orders" target="_blank" rel="noopener" class="chatbot-link">View My Orders</a>',
            type: 'static'
        },
        {
            keywords: ['thanks', 'thank you', 'thankyou', 'bye', 'goodbye', 'see you', 'that\'s all', 'thats all', 'ok thanks', 'appreciate it'],
            answer: 'You\'re welcome! Feel free to come back anytime if you have more questions. Have a great day!',
            type: 'static'
        },
        {
            keywords: ['contact', 'reach you', 'email', 'phone', 'call', 'support', 'help', 'get in touch', 'how to contact', 'contact info', 'contact us', 'speak to someone', 'customer service', 'support team'],
            answer: 'You can reach us through our Contact page at contact.php. Our team will get back to you within 24-48 hours.',
            type: 'static'
        },
        {
            keywords: ['warranty', 'guarantee', 'defect', 'broken', 'damaged', 'repair', 'defective', 'not working', 'issue', 'problem', '1 year', 'one year', 'warranty period'],
            answer: 'We offer a 1-year warranty on all our products. If you receive a damaged or defective item, please contact us immediately for a replacement or repair.',
            type: 'static'
        }
    ];

    // Fallback message for unrecognized input
    const FALLBACK_MESSAGE = 'Please ask your question in English so I can help you better.';

    // DOM elements
    let chatBubble, chatWindow, chatMessages, chatInput, sendButton, closeBtn;

    // Initialize chatbot
    function init() {
        // Create DOM elements if they don't exist
        chatBubble = document.getElementById('chatbot-bubble');
        chatWindow = document.getElementById('chatbot-window');
        chatMessages = document.getElementById('chatbot-messages');
        chatInput = document.getElementById('chatbot-input');
        sendButton = document.getElementById('chatbot-send');
        closeBtn = document.getElementById('chatbot-close');

        if (!chatBubble || !chatWindow) return;

        // Event listeners
        chatBubble.addEventListener('click', toggleChat);
        closeBtn.addEventListener('click', toggleChat);
        sendButton.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') sendMessage();
        });

        // Add welcome message
        addBotMessage('Hi! I can help with shipping, returns, warranty, and more. Ask me anything!');
    }

    // Toggle chat window open/close
    function toggleChat() {
        chatWindow.classList.toggle('open');
        if (chatWindow.classList.contains('open')) {
            chatInput.focus();
        }
    }

    // Send user message
    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        // Add user message to chat
        addUserMessage(message);
        chatInput.value = '';

        // Process message and get response
        processMessage(message);
    }

    // Add user message to chat
    function addUserMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message user';
        messageDiv.textContent = message;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Add bot message to chat
    function addBotMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'chatbot-message bot';
        messageDiv.innerHTML = message;
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }

    // Scroll chat to bottom
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Levenshtein distance for fuzzy matching (handles typos)
    function levenshteinDistance(str1, str2) {
        const m = str1.length;
        const n = str2.length;
        const dp = Array(m + 1).fill(null).map(() => Array(n + 1).fill(0));

        for (let i = 0; i <= m; i++) dp[i][0] = i;
        for (let j = 0; j <= n; j++) dp[0][j] = j;

        for (let i = 1; i <= m; i++) {
            for (let j = 1; j <= n; j++) {
                if (str1[i - 1] === str2[j - 1]) {
                    dp[i][j] = dp[i - 1][j - 1];
                } else {
                    dp[i][j] = 1 + Math.min(dp[i - 1][j], dp[i][j - 1], dp[i - 1][j - 1]);
                }
            }
        }
        return dp[m][n];
    }

    // Process user message and find matching topic
    function processMessage(message) {
        const lowerMessage = message.toLowerCase();
        const words = lowerMessage.split(/\s+/);
        
        // Find matching topic
        let matchedTopic = null;
        let maxMatches = 0;

        for (const topic of topics) {
            let matchCount = 0;
            for (const keyword of topic.keywords) {
                const lowerKeyword = keyword.toLowerCase();
                
                // Check if keyword is a multi-word phrase
                const isMultiWord = lowerKeyword.includes(' ');
                
                // For short keywords (≤3 chars), use word-boundary matching
                if (lowerKeyword.length <= 3) {
                    if (words.includes(lowerKeyword)) {
                        matchCount++;
                        continue;
                    }
                } else if (isMultiWord) {
                    // Multi-word phrases: only exact substring matching, no fuzzy matching
                    if (lowerMessage.includes(lowerKeyword)) {
                        matchCount++;
                        continue;
                    }
                } else {
                    // Single-word keywords (4+ chars): try substring first
                    if (lowerMessage.includes(lowerKeyword)) {
                        matchCount++;
                        continue;
                    }
                    
                    // Then try fuzzy matching for each word in user message (only for single-word keywords)
                    for (const word of words) {
                        if (word.length >= 4 && levenshteinDistance(word, lowerKeyword) <= 2) {
                            matchCount++;
                            break;
                        }
                    }
                }
            }
            if (matchCount > maxMatches) {
                maxMatches = matchCount;
                matchedTopic = topic;
            }
        }

        // Handle response
        if (matchedTopic && maxMatches > 0) {
            if (matchedTopic.type === 'dynamic' && matchedTopic.answer === 'dynamic_shipping') {
                // Fetch dynamic shipping cost
                fetchShippingCost();
            } else {
                // Static answer
                addBotMessage(matchedTopic.answer);
            }
        } else {
            // No match - fallback
            addBotMessage(FALLBACK_MESSAGE);
        }
    }

    // Fetch shipping cost from API
    function fetchShippingCost() {
        fetch(CHATBOT_BASE_URL + '/api/shipping-cost.php')
            .then(response => response.json())
            .then(data => {
                if (data.is_free) {
                    addBotMessage('We are currently offering free shipping on all orders. This is a limited-time offer and subject to change without notice.');
                } else {
                    addBotMessage('Standard shipping is $' + data.cost.toFixed(2) + ' per order, calculated at checkout.');
                }
            })
            .catch(error => {
                addBotMessage('Sorry, I couldn\'t fetch the current shipping information. Please check our Shipping page for details.');
            });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
