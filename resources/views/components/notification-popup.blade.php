<!-- resources/views/components/notification-popup.blade.php -->
<div id="notification-container" class="notification-container"></div>

<style>
.notification-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 400px;
}

.notification-popup {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 16px 20px;
    margin-bottom: 10px;
    display: flex;
    align-items: start;
    gap: 12px;
    animation: slideIn 0.3s ease-out;
    position: relative;
    overflow: hidden;
}

.notification-popup.hiding {
    animation: slideOut 0.3s ease-out forwards;
}

@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOut {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(400px);
        opacity: 0;
    }
}

.notification-popup.login {
    border-left: 4px solid #10b981;
}

.notification-popup.registration {
    border-left: 4px solid #3b82f6;
}

.notification-popup.info {
    border-left: 4px solid #06b6d4;
}

.notification-icon {
    flex-shrink: 0;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    color: white;
    font-size: 14px;
}

.notification-popup.login .notification-icon {
    background: #10b981;
}

.notification-popup.registration .notification-icon {
    background: #3b82f6;
}

.notification-popup.info .notification-icon {
    background: #06b6d4;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 4px;
    color: #1f2937;
}

.notification-message {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 4px;
}

.notification-time {
    font-size: 11px;
    color: #9ca3af;
}

.notification-close {
    position: absolute;
    top: 8px;
    right: 8px;
    background: none;
    border: none;
    color: #9ca3af;
    cursor: pointer;
    font-size: 18px;
    line-height: 1;
    padding: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: background 0.2s;
}

.notification-close:hover {
    background: #f3f4f6;
    color: #4b5563;
}

.notification-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    background: currentColor;
    opacity: 0.3;
    animation: progress 5s linear;
}

@keyframes progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    @auth
    const notifications = @json(auth()->user()->unreadNotifications);
    
    notifications.forEach((notification, index) => {
        setTimeout(() => {
            showNotification(notification);
        }, index * 300);
    });
    @endauth
});

function showNotification(notification) {
    const container = document.getElementById('notification-container');
    const data = notification.data;
    const type = data.type || 'info';
    
    const notificationEl = document.createElement('div');
    notificationEl.className = `notification-popup ${type}`;
    notificationEl.innerHTML = `
        <div class="notification-icon">✓</div>
        <div class="notification-content">
            <div class="notification-title">${capitalizeFirst(type)}</div>
            <div class="notification-message">${data.message}</div>
            <div class="notification-time">${formatTime(notification.created_at)}</div>
        </div>
        <button class="notification-close" onclick="closeNotification(this, '${notification.id}')">×</button>
        <div class="notification-progress"></div>
    `;
    
    container.appendChild(notificationEl);
    
    setTimeout(() => {
        closeNotification(notificationEl.querySelector('.notification-close'), notification.id);
    }, 5000);
}

function closeNotification(button, notificationId) {
    const notification = button.closest('.notification-popup');
    if (!notification) return;
    
    notification.classList.add('hiding');
    
    setTimeout(() => {
        notification.remove();
    }, 300);
    
    if (notificationId) {
        markAsRead(notificationId);
    }
}

function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    }).catch(err => console.error('Error marking notification as read:', err));
}

function capitalizeFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function formatTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    return Math.floor(diff / 86400) + ' days ago';
}
</script>