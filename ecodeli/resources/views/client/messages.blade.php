
@extends('layouts.app')

@section('title', 'Messagerie')

@section('content')
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<!-- Service MessageService intégré -->
<script>
class MessageService {
    constructor() {
        this.baseURL = '/api/api';
        this.token = localStorage.getItem('token');
    }

    getHeaders() {
        return {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${this.token}`
        };
    }

    async getConversations() {
        try {
            const response = await fetch(`${this.baseURL}/messages/conversations`, {
                headers: this.getHeaders()
            });
            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la récupération des conversations:', error);
            throw error;
        }
    }

    async getMessages(contactId, page = 1, limit = 20) {
        try {
            const response = await fetch(
                `${this.baseURL}/messages/conversation/${contactId}?page=${page}&limit=${limit}`,
                { headers: this.getHeaders() }
            );
            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la récupération des messages:', error);
            throw error;
        }
    }

    async sendMessage(receiverId, content) {
        try {
            const response = await fetch(`${this.baseURL}/messages/send`, {
                method: 'POST',
                headers: this.getHeaders(),
                body: JSON.stringify({
                    receiver_id: receiverId,
                    content: content
                })
            });

            // Vérifier si la réponse est réussie
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            // Vérifier si l'API retourne un succès
            if (!result.success) {
                throw new Error(result.message || 'Erreur lors de l\'envoi du message');
            }

            return result;
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            throw error; // Re-lancer l'erreur pour que l'appelant puisse la gérer
        }
    }

    async searchUsers(query) {
        try {
            const response = await fetch(
                `${this.baseURL}/messages/search-users?query=${encodeURIComponent(query)}`,
                { headers: this.getHeaders() }
            );
            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
            throw error;
        }
    }

    async getUnreadCount() {
        try {
            const response = await fetch(`${this.baseURL}/messages/unread-count`, {
                headers: this.getHeaders()
            });
            return await response.json();
        } catch (error) {
            console.error('Erreur lors de la récupération du nombre non lu:', error);
            throw error;
        }
    }
}

// Instance globale
window.messageService = new MessageService();
</script>

<div class="min-h-screen bg-gray-50" x-data="messagingApp()">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-2xl font-bold text-gray-900">Messagerie</h1>
                <div class="flex items-center space-x-4">
                    <!-- Compteur messages non lus -->
                    <div x-show="unreadCount > 0"
                         class="bg-red-500 text-white px-2 py-1 rounded-full text-sm font-medium">
                        <span x-text="unreadCount"></span> non lu(s)
                    </div>
                    <!-- Bouton nouveau message -->
                    <button @click="showNewMessageModal = true"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium">
                        <i class="fas fa-plus mr-2"></i>Nouveau message
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex h-[calc(100vh-12rem)] bg-white rounded-lg shadow-lg overflow-hidden">

            <!-- Sidebar - Liste des conversations -->
            <div class="w-1/3 border-r border-gray-200 flex flex-col">
                <!-- Recherche -->
                <div class="p-4 border-b border-gray-200">
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               @input="searchConversations()"
                               placeholder="Rechercher une conversation..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-green-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Liste des conversations -->
                <div class="flex-1 overflow-y-auto">
                    <template x-for="conversation in filteredConversations" :key="conversation.contact_id">
                        <div @click="selectConversation(conversation)"
                             :class="selectedConversation?.contact_id === conversation.contact_id ?
                                     'bg-green-50 border-r-4 border-green-500' : 'hover:bg-gray-50'"
                             class="p-4 border-b border-gray-100 cursor-pointer transition-colors">
                            <div class="flex items-center space-x-3">
                                <!-- Avatar -->
                                <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <span x-text="conversation.contact_name?.charAt(0)?.toUpperCase() || '?'"></span>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-center">
                                        <p class="text-sm font-medium text-gray-900 truncate"
                                           x-text="conversation.contact_name || 'Utilisateur'"></p>
                                        <p class="text-xs text-gray-500"
                                           x-text="formatDate(conversation.last_message_date)"></p>
                                    </div>
                                    <p class="text-sm text-gray-600 truncate"
                                       x-text="conversation.last_message || 'Aucun message'"></p>

                                    <!-- Badge non lu -->
                                    <div x-show="conversation.unread_count > 0"
                                         class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 mt-1">
                                        <span x-text="conversation.unread_count"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Message si aucune conversation -->
                    <div x-show="conversations.length === 0" class="p-8 text-center text-gray-500">
                        <i class="fas fa-comments text-4xl mb-4"></i>
                        <p>Aucune conversation pour le moment</p>
                        <p class="text-sm">Commencez une nouvelle conversation !</p>
                    </div>
                </div>
            </div>

            <!-- Zone de chat -->
            <div class="flex-1 flex flex-col">
                <template x-if="selectedConversation">
                    <div class="flex flex-col h-full">
                        <!-- Header du chat -->
                        <div class="p-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold">
                                    <span x-text="selectedConversation.contact_name?.charAt(0)?.toUpperCase() || '?'"></span>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-900" x-text="selectedConversation.contact_name || 'Utilisateur'"></h3>
                                    <p class="text-sm text-gray-500">En ligne</p>
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4 message-container" x-ref="messagesContainer">
                            <template x-for="message in messages" :key="message.message_id">
                                <div :class="message.sender_id == currentUserId ? 'flex justify-end' : 'flex justify-start'">
                                    <div :class="message.sender_id == currentUserId ?
                                                'bg-green-500 text-white' : 'bg-gray-200 text-gray-900'"
                                         class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg">
                                        <p x-text="message.content"></p>
                                        <p :class="message.sender_id == currentUserId ? 'text-green-100' : 'text-gray-500'"
                                           class="text-xs mt-1"
                                           x-text="formatDateTime(message.send_date)"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Zone de saisie -->
                        <div class="p-4 border-t border-gray-200">
                            <form @submit.prevent="sendMessage()" class="flex space-x-2">
                                <input type="text"
                                       x-model="newMessage"
                                       placeholder="Tapez votre message..."
                                       class="flex-1 border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500"
                                       :disabled="sendingMessage">
                                <button type="submit"
                                        :disabled="!newMessage.trim() || sendingMessage"
                                        class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </template>

                <!-- Message par défaut -->
                <template x-if="!selectedConversation">
                    <div class="flex-1 flex items-center justify-center text-gray-500">
                        <div class="text-center">
                            <i class="fas fa-comment-dots text-6xl mb-4"></i>
                            <h3 class="text-xl font-medium mb-2">Sélectionnez une conversation</h3>
                            <p>Choisissez une conversation dans la liste pour commencer à discuter</p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal nouveau message -->
    <div x-show="showNewMessageModal"
         x-transition
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-medium mb-4">Nouveau message</h3>

            <!-- Recherche utilisateur -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Destinataire</label>
                <input type="text"
                       x-model="userSearchQuery"
                       @input="searchUsers()"
                       placeholder="Rechercher un utilisateur..."
                       class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500">

                <!-- Résultats de recherche -->
                <div x-show="searchResults.length > 0" class="mt-2 border border-gray-200 rounded-md max-h-40 overflow-y-auto">
                    <template x-for="user in searchResults" :key="user.user_id">
                        <div @click="selectUser(user)"
                             class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                                    <span x-text="user.name?.charAt(0)?.toUpperCase() || '?'"></span>
                                </div>
                                <div>
                                    <p class="font-medium" x-text="user.name"></p>
                                    <p class="text-sm text-gray-600" x-text="user.email"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Utilisateur sélectionné -->
            <div x-show="selectedUser" class="mb-4 p-3 bg-green-50 rounded-md">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        <span x-text="selectedUser?.name?.charAt(0)?.toUpperCase() || '?'"></span>
                    </div>
                    <div>
                        <p class="font-medium" x-text="selectedUser?.name"></p>
                        <p class="text-sm text-gray-600" x-text="selectedUser?.email"></p>
                    </div>
                    <button @click="selectedUser = null" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <!-- Message -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                <textarea x-model="newConversationMessage"
                          rows="3"
                          placeholder="Tapez votre message..."
                          class="w-full border border-gray-300 rounded-md px-3 py-2 focus:ring-2 focus:ring-green-500"></textarea>
            </div>

            <!-- Boutons -->
            <div class="flex justify-end space-x-3">
                <button @click="closeNewMessageModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                    Annuler
                </button>
                <button @click="sendNewMessage()"
                        :disabled="!selectedUser || !newConversationMessage.trim()"
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white rounded-md">
                    Envoyer
                </button>
            </div>
        </div>
    </div>

    <!-- Loading spinner -->
    <div x-show="loading"
         class="fixed inset-0 bg-black bg-opacity-25 flex items-center justify-center z-40">
        <div class="bg-white p-4 rounded-lg">
            <i class="fas fa-spinner fa-spin text-2xl text-green-600"></i>
        </div>
    </div>
</div>

<script>
function messagingApp() {
    return {
        // État principal
        conversations: [],
        filteredConversations: [],
        selectedConversation: null,
        messages: [],
        currentUserId: null,

        // Interface
        loading: false,
        searchQuery: '',
        newMessage: '',
        sendingMessage: false,

        // Modal nouveau message
        showNewMessageModal: false,
        userSearchQuery: '',
        searchResults: [],
        selectedUser: null,
        newConversationMessage: '',

        // Compteurs
        unreadCount: 0,

        // Initialisation
        async init() {
            // Récupérer l'ID utilisateur depuis l'API
            await this.getCurrentUser();
            await this.loadConversations();
            await this.loadUnreadCount();

            // Actualiser toutes les 30 secondes
            setInterval(() => {
                this.loadConversations();
                this.loadUnreadCount();
            }, 30000);
        },

        // Récupérer l'utilisateur actuel
        async getCurrentUser() {
            try {
                const response = await fetch('/api/api/user', {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`
                    }
                });
                const data = await response.json();
                if (data.success !== false) {
                    this.currentUserId = data.user.user_id;
                }
            } catch (error) {
                console.error('Erreur lors de la récupération de l\'utilisateur:', error);
            }
        },

        // Charger les conversations
        async loadConversations() {
            try {
                const response = await messageService.getConversations();
                if (response.success) {
                    this.conversations = response.data;
                    this.filteredConversations = response.data;
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },

        // Charger le nombre de messages non lus
        async loadUnreadCount() {
            try {
                const response = await messageService.getUnreadCount();
                if (response.success) {
                    this.unreadCount = response.data.unread_count;
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },

        // Rechercher dans les conversations
        searchConversations() {
            if (!this.searchQuery.trim()) {
                this.filteredConversations = this.conversations;
                return;
            }

            this.filteredConversations = this.conversations.filter(conv =>
                (conv.contact_name && conv.contact_name.toLowerCase().includes(this.searchQuery.toLowerCase())) ||
                (conv.last_message && conv.last_message.toLowerCase().includes(this.searchQuery.toLowerCase()))
            );
        },

        // Sélectionner une conversation
        async selectConversation(conversation) {
            this.selectedConversation = conversation;
            await this.loadMessages(conversation.contact_id);
        },

        // Charger les messages
        async loadMessages(contactId) {
            this.loading = true;
            try {
                const response = await messageService.getMessages(contactId);
                if (response.success) {
                    this.messages = response.data.messages;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                this.loading = false;
            }
        },

        // Envoyer un message
        async sendMessage() {
            if (!this.newMessage.trim() || !this.selectedConversation) return;

            this.sendingMessage = true;
            try {
                const response = await messageService.sendMessage(
                    this.selectedConversation.contact_id,
                    this.newMessage
                );

                if (response.success) {
                    this.messages.push(response.data);
                    this.newMessage = '';
                    this.$nextTick(() => {
                        this.scrollToBottom();
                    });

                    // Recharger les conversations pour mettre à jour
                    await this.loadConversations();
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du message');
            } finally {
                this.sendingMessage = false;
            }
        },

        // Rechercher des utilisateurs
        async searchUsers() {
            if (this.userSearchQuery.length < 2) {
                this.searchResults = [];
                return;
            }

            try {
                const response = await messageService.searchUsers(this.userSearchQuery);
                if (response.success) {
                    // Correction: utiliser response.data directement
                    this.searchResults = response.data;
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        },

        // Sélectionner un utilisateur
        selectUser(user) {
            this.selectedUser = user;
            this.searchResults = [];
            this.userSearchQuery = user.name;
        },

        // Envoyer un nouveau message
        async sendNewMessage() {
            if (!this.selectedUser || !this.newConversationMessage.trim()) return;

            this.loading = true;
            try {
                const response = await messageService.sendMessage(
                    this.selectedUser.user_id,
                    this.newConversationMessage
                );

                if (response.success) {
                    this.closeNewMessageModal();
                    await this.loadConversations();

                    // Sélectionner la nouvelle conversation
                    const newConv = this.conversations.find(c => c.contact_id === this.selectedUser.user_id);
                    if (newConv) {
                        await this.selectConversation(newConv);
                    }
                }
            } catch (error) {
                console.error('Erreur:', error);
            } finally {
                this.loading = false;
            }
        },

        // Fermer le modal
        closeNewMessageModal() {
            this.showNewMessageModal = false;
            this.userSearchQuery = '';
            this.searchResults = [];
            this.selectedUser = null;
            this.newConversationMessage = '';
        },

        // Scroll vers le bas
        scrollToBottom() {
            if (this.$refs.messagesContainer) {
                this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
            }
        },

        // Formatage des dates
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;

            if (diff < 24 * 60 * 60 * 1000) {
                return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            } else {
                return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
            }
        },

        formatDateTime(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}
</script>

<style>
/* Styles personnalisés pour la messagerie */
.message-container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.message-container::-webkit-scrollbar {
    width: 6px;
}

.message-container::-webkit-scrollbar-track {
    background: #f7fafc;
}

.message-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.message-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Animation pour les nouveaux messages */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.new-message {
    animation: slideIn 0.3s ease-out;
}

/* Animation spinner */
.fa-spin {
    animation: fa-spin 1s infinite linear;
}

@keyframes fa-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

@endsection
