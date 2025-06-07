<?php 
$pageTitle = 'Agents - OpenAI Webchat';
ob_start(); 
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="md:flex md:items-center md:justify-between mb-8">
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                My Agents
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Create and manage your AI agents with custom tools and capabilities
            </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
            <button 
                id="create-agent-btn"
                class="ml-3 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create Agent
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Agents</dt>
                            <dd class="text-lg font-medium text-gray-900"><?= count($agents) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Tools Available</dt>
                            <dd class="text-lg font-medium text-gray-900">3</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Agents</dt>
                            <dd class="text-lg font-medium text-gray-900"><?= count(array_filter($agents, fn($a) => $a->isActive())) ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Agents List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Your Agents</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage your custom AI agents and their capabilities</p>
        </div>
        
        <?php if (empty($agents)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No agents</h3>
                <p class="mt-1 text-sm text-gray-500">Get started by creating your first AI agent.</p>
                <div class="mt-6">
                    <button 
                        id="create-first-agent-btn"
                        class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Create Agent
                    </button>
                </div>
            </div>
        <?php else: ?>
            <ul class="divide-y divide-gray-200">
                <?php foreach ($agents as $agent): ?>
                <li class="px-4 py-4 sm:px-6 hover:bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                    <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="flex items-center">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($agent->getName()) ?></p>
                                    <?php if (!$agent->isActive()): ?>
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars(substr($agent->getInstructions(), 0, 100)) ?>...</p>
                                <div class="flex items-center mt-2 space-x-4">
                                    <span class="text-xs text-gray-500">Model: <?= htmlspecialchars($agent->getModel()) ?></span>
                                    <span class="text-xs text-gray-500">Tools: <?= count($agent->getTools()) ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button 
                                onclick="testAgent(<?= $agent->getId() ?>)"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Test
                            </button>
                            <button 
                                onclick="editAgent(<?= $agent->getId() ?>)"
                                class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                Edit
                            </button>
                            <button 
                                onclick="deleteAgent(<?= $agent->getId() ?>)"
                                class="inline-flex items-center px-3 py-1.5 border border-red-300 shadow-sm text-xs font-medium rounded text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Create Agent Modal -->
<div id="create-agent-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Agent</h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            
            <form id="create-agent-form" class="space-y-4">
                <div>
                    <label for="agent-name" class="block text-sm font-medium text-gray-700">Agent Name</label>
                    <input 
                        type="text" 
                        id="agent-name" 
                        name="name" 
                        required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        placeholder="e.g., Code Assistant, Research Helper"
                    >
                </div>
                
                <div>
                    <label for="agent-instructions" class="block text-sm font-medium text-gray-700">Instructions</label>
                    <textarea 
                        id="agent-instructions" 
                        name="instructions" 
                        rows="4" 
                        required
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                        placeholder="Describe what this agent should do and how it should behave..."
                    ></textarea>
                </div>
                
                <div>
                    <label for="agent-model" class="block text-sm font-medium text-gray-700">Model</label>
                    <select 
                        id="agent-model" 
                        name="model"
                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500"
                    >
                        <option value="gpt-4o-mini">GPT-4O Mini (Fast & Cost-effective)</option>
                        <option value="gpt-4o">GPT-4O (Most Capable)</option>
                        <option value="gpt-4">GPT-4 (Legacy)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Available Tools</label>
                    <div class="mt-2 space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="tools" value="Calculator" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-900">Calculator - Mathematical calculations</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="tools" value="WebSearch" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-900">Web Search - Search for current information</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="tools" value="Weather" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="ml-2 text-sm text-gray-900">Weather - Get weather information for any location</span>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button 
                        type="button" 
                        onclick="closeCreateModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Create Agent
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const createAgentBtns = [
        document.getElementById('create-agent-btn'),
        document.getElementById('create-first-agent-btn')
    ].filter(Boolean);
    
    const createAgentModal = document.getElementById('create-agent-modal');
    const createAgentForm = document.getElementById('create-agent-form');
    
    // Show create modal
    createAgentBtns.forEach(btn => {
        btn?.addEventListener('click', function() {
            createAgentModal.classList.remove('hidden');
        });
    });
    
    // Handle form submission
    createAgentForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(createAgentForm);
        const tools = Array.from(createAgentForm.querySelectorAll('input[name="tools"]:checked'))
                          .map(checkbox => checkbox.value);
        
        const agentData = {
            name: formData.get('name'),
            instructions: formData.get('instructions'),
            model: formData.get('model'),
            tools: tools
        };
        
        try {
            const response = await fetch('/api/agents', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(agentData)
            });
            
            if (!response.ok) {
                throw new Error('Failed to create agent');
            }
            
            const result = await response.json();
            
            // Reload page to show new agent
            window.location.reload();
            
        } catch (error) {
            console.error('Error creating agent:', error);
            alert('Failed to create agent. Please try again.');
        }
    });
});

function closeCreateModal() {
    const modal = document.getElementById('create-agent-modal');
    modal.classList.add('hidden');
    
    // Reset form
    document.getElementById('create-agent-form').reset();
}

function testAgent(agentId) {
    // Redirect to chat with agent selection
    window.location.href = `/chat?agent=${agentId}`;
}

function editAgent(agentId) {
    // For now, show a simple alert
    // In a full implementation, you'd show an edit modal
    alert(`Edit agent ${agentId} - Feature coming soon!`);
}

async function deleteAgent(agentId) {
    if (!confirm('Are you sure you want to delete this agent? This action cannot be undone.')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/agents/${agentId}`, {
            method: 'DELETE',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error('Failed to delete agent');
        }
        
        // Reload page to update list
        window.location.reload();
        
    } catch (error) {
        console.error('Error deleting agent:', error);
        alert('Failed to delete agent. Please try again.');
    }
}

// Close modal when clicking outside
document.getElementById('create-agent-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreateModal();
    }
});
</script>

<?php 
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>