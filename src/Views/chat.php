<?php 
$pageTitle = 'Chat - OpenAI Webchat';
ob_start(); 
?>

<div class="flex h-screen bg-gray-100">
    <!-- Sidebar - Thread List -->
    <div class="w-80 bg-white shadow-lg border-r border-gray-200 flex flex-col">
        <!-- Sidebar Header -->
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Conversations</h2>
                <button 
                    id="new-thread-btn"
                    class="bg-primary-600 text-white px-3 py-1.5 rounded-md text-sm font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                    New Chat
                </button>
            </div>
        </div>
        
        <!-- Thread List -->
        <div class="flex-1 overflow-y-auto">
            <div id="thread-list" class="p-2">
                <?php foreach ($threads as $thread): ?>
                <div 
                    class="thread-item p-3 mb-2 rounded-lg cursor-pointer hover:bg-gray-50 <?= $thread['id'] == $currentThread['id'] ? 'bg-primary-50 border border-primary-200' : 'bg-white border border-gray-200' ?>"
                    data-thread-id="<?= $thread['id'] ?>"
                >
                    <div class="font-medium text-gray-900 truncate">
                        <?= htmlspecialchars($thread['title']) ?>
                    </div>
                    <div class="text-sm text-gray-500 mt-1">
                        <?= $thread['message_count'] ?? 0 ?> messages
                    </div>
                    <div class="text-xs text-gray-400 mt-1">
                        <?= date('M j, Y', strtotime($thread['created_at'])) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
        <!-- Chat Header -->
        <div class="bg-white p-4 border-b border-gray-200 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-gray-900" id="current-thread-title">
                        <?= htmlspecialchars($currentThread['title']) ?>
                    </h1>
                    <div class="flex items-center space-x-4 mt-1">
                        <p class="text-sm text-gray-500">
                            <span id="agent-status">AI-powered conversation</span>
                        </p>
                        <!-- Agent Selection -->
                        <?php if (!empty($availableAgents)): ?>
                        <div class="flex items-center space-x-2">
                            <label for="agent-select" class="text-sm text-gray-500">Test Specific Agent:</label>
                            <select id="agent-select" class="text-sm border border-gray-300 rounded px-2 py-1 focus:ring-primary-500 focus:border-primary-500">
                                <option value="">Default Assistant</option>
                                <?php foreach ($availableAgents as $agent): ?>
                                <option value="<?= $agent->getId() ?>" <?= $selectedAgentId == $agent->getId() ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($agent->getName()) ?>
                                    <?php if (!empty($agent->getTools())): ?>
                                        (<?= count($agent->getTools()) ?> tools)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500">No agents available</span>
                            <a href="/agents" class="text-sm text-primary-600 hover:text-primary-500">Create one</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Online
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Messages Container -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" id="messages-container">
            <?php foreach ($messages as $message): ?>
            <div class="message-item <?= $message['role'] === 'user' ? 'ml-auto' : 'mr-auto' ?> max-w-3xl">
                <div class="flex items-start space-x-3 <?= $message['role'] === 'user' ? 'flex-row-reverse space-x-reverse' : '' ?>">
                    <!-- Avatar -->
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center <?= $message['role'] === 'user' ? 'bg-primary-500 text-white' : 'bg-gray-500 text-white' ?>">
                            <?= $message['role'] === 'user' ? 'U' : 'AI' ?>
                        </div>
                    </div>
                    
                    <!-- Message Content -->
                    <div class="flex-1">
                        <div class="<?= $message['role'] === 'user' ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200' ?> rounded-lg p-3 shadow-sm">
                            <p class="text-sm <?= $message['role'] === 'user' ? 'text-white' : 'text-gray-900' ?>">
                                <?= nl2br(htmlspecialchars($message['content'])) ?>
                            </p>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 <?= $message['role'] === 'user' ? 'text-right' : 'text-left' ?>">
                            <?= date('g:i A', strtotime($message['created_at'])) ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Typing Indicator (hidden by default) -->
            <div id="typing-indicator" class="message-item mr-auto max-w-3xl hidden">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-500 text-white">
                            AI
                        </div>
                    </div>
                    <div class="flex-1">
                        <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Message Input -->
        <div class="bg-white border-t border-gray-200 p-4">
            <form id="message-form" class="flex space-x-4">
                <div class="flex-1">
                    <textarea 
                        id="message-input"
                        rows="1"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-primary-500 focus:border-primary-500 resize-none"
                        placeholder="Type your message..."
                        style="min-height: 40px; max-height: 120px;"
                    ></textarea>
                </div>
                <button 
                    type="submit"
                    id="send-button"
                    class="bg-primary-600 text-white px-6 py-2 rounded-md font-medium hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Send
                </button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Chat Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageForm = document.getElementById('message-form');
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const messagesContainer = document.getElementById('messages-container');
    const typingIndicator = document.getElementById('typing-indicator');
    const newThreadBtn = document.getElementById('new-thread-btn');
    const agentSelect = document.getElementById('agent-select');
    const agentStatus = document.getElementById('agent-status');
    
    let currentThreadId = <?= $currentThread['id'] ?>;
    let currentAgentId = <?= $selectedAgentId ? $selectedAgentId : 'null' ?>;
    
    // Update agent status when selection changes
    if (agentSelect) {
        agentSelect.addEventListener('change', function() {
            currentAgentId = this.value || null;
            updateAgentStatus();
        });
        
        // Set initial status
        updateAgentStatus();
    }
    
    function updateAgentStatus() {
        if (!agentStatus) return;
        
        if (currentAgentId) {
            const selectedOption = agentSelect.querySelector(`option[value="${currentAgentId}"]`);
            if (selectedOption) {
                agentStatus.textContent = `Using agent: ${selectedOption.textContent}`;
            }
        } else {
            agentStatus.textContent = 'Default OpenAI assistant';
        }
    }
    
    // Auto-resize textarea
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
    
    // Send message on Enter (but allow Shift+Enter for new lines)
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            messageForm.dispatchEvent(new Event('submit'));
        }
    });
    
    // Handle message submission
    messageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        console.log('🚀 Sending message:', message);
        console.log('🎯 Thread ID:', currentThreadId);
        
        // Disable form
        sendButton.disabled = true;
        messageInput.disabled = true;
        
        try {
            // Add user message to UI immediately
            addMessageToUI('user', message);
            messageInput.value = '';
            messageInput.style.height = 'auto';
            
            // Show typing indicator
            showTypingIndicator();
            
            let url, requestBody;
            
            // Determine if we should use an agent or regular chat
            if (currentAgentId) {
                // Use agent endpoint
                url = `/api/agents/${currentAgentId}/run`;
                requestBody = {
                    message: message,
                    threadId: currentThreadId
                };
                console.log('🤖 Using agent ID:', currentAgentId);
            } else {
                // Use regular chat endpoint  
                url = `/api/threads/${currentThreadId}/messages`;
                requestBody = { message: message };
                console.log('💬 Using default chat');
            }
            
            console.log('📡 Making request to:', url);
            
            // Send to API
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(requestBody)
            });
            
            console.log('📊 Response status:', response.status);
            console.log('📊 Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Response not OK:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            // Get response text first to debug
            const responseText = await response.text();
            console.log('📝 Raw response:', responseText.substring(0, 500));
            
            // Try to parse as JSON
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (jsonError) {
                console.error('❌ JSON Parse Error:', jsonError);
                console.error('❌ Response was not JSON:', responseText);
                throw new Error('Server returned invalid JSON response: ' + responseText.substring(0, 100));
            }
            
            console.log('✅ API Response:', result);
            
            // Hide typing indicator
            hideTypingIndicator();
            
            // Add AI response to UI
            if (result.response) {
                addMessageToUI('assistant', result.response);
            } else {
                console.error('❌ No response in result:', result);
                throw new Error('No response received from AI');
            }
            
        } catch (error) {
            hideTypingIndicator();
            console.error('💥 Full error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name
            });
            alert('Failed to send message: ' + error.message);
        } finally {
            // Re-enable form
            sendButton.disabled = false;
            messageInput.disabled = false;
            messageInput.focus();
        }
    });
    
    // Add message to UI
    function addMessageToUI(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message-item ${role === 'user' ? 'ml-auto' : 'mr-auto'} max-w-3xl`;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
        
        messageDiv.innerHTML = `
            <div class="flex items-start space-x-3 ${role === 'user' ? 'flex-row-reverse space-x-reverse' : ''}">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center ${role === 'user' ? 'bg-primary-500 text-white' : 'bg-gray-500 text-white'}">
                        ${role === 'user' ? 'U' : 'AI'}
                    </div>
                </div>
                <div class="flex-1">
                    <div class="${role === 'user' ? 'bg-primary-600 text-white' : 'bg-white border border-gray-200'} rounded-lg p-3 shadow-sm">
                        <p class="text-sm ${role === 'user' ? 'text-white' : 'text-gray-900'}">
                            ${content.replace(/\n/g, '<br>')}
                        </p>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 ${role === 'user' ? 'text-right' : 'text-left'}">
                        ${timeString}
                    </div>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    // Show/hide typing indicator
    function showTypingIndicator() {
        typingIndicator.classList.remove('hidden');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    function hideTypingIndicator() {
        typingIndicator.classList.add('hidden');
    }
    
    // New thread creation
    newThreadBtn.addEventListener('click', async function() {
        try {
            const response = await fetch('/api/threads', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ title: 'New Chat' })
            });
            
            if (!response.ok) {
                throw new Error('Failed to create thread');
            }
            
            const newThread = await response.json();
            
            // Redirect to new thread (simple approach for now)
            window.location.reload();
            
        } catch (error) {
            console.error('Error creating thread:', error);
            alert('Failed to create new chat. Please try again.');
        }
    });
    
    // Focus message input on load
    messageInput.focus();
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>