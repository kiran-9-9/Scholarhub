// Function to show notification
function showNotification(message, type = 'error', title = '') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    
    let icon = 'exclamation-circle';
    if (type === 'warning') icon = 'exclamation-triangle';
    if (type === 'success') icon = 'check-circle';
    
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-header">
                <i class="fas fa-${icon}"></i>
                <span class="notification-title">${title}</span>
            </div>
            <div class="notification-body">${message}</div>
        </div>
        <button onclick="this.parentElement.remove()" class="notification-close">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add styles
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.backgroundColor = type === 'error' ? '#dc3545' : 
                                      type === 'warning' ? '#ffc107' : '#28a745';
    notification.style.color = type === 'warning' ? '#000' : '#fff';
    notification.style.padding = '15px 25px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
    notification.style.display = 'flex';
    notification.style.alignItems = 'center';
    notification.style.justifyContent = 'space-between';
    notification.style.minWidth = '350px';
    notification.style.zIndex = '9999';
    notification.style.animation = 'slideIn 0.5s ease-out';

    // Add the notification to the document
    document.body.appendChild(notification);

    // Remove notification after 5 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.5s ease-out';
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .notification-close {
        background: none;
        border: none;
        color: white;
        cursor: pointer;
        padding: 0 5px;
        margin-left: 15px;
    }
    .notification-close:hover {
        opacity: 0.8;
    }
`;
document.head.appendChild(style); 