import { ref } from 'vue'

const notifications = ref([])
let nextId = 0

export function useAdminNotification() {
    const showNotification = (message, options = {}) => {
        const {
            title = '',
            type = 'info',
            duration = 5000,
        } = options

        const id = nextId++
        const notification = {
            id,
            message,
            title,
            type,
            duration,
        }

        notifications.value.push(notification)

        if (duration > 0) {
            setTimeout(() => {
                removeNotification(id)
            }, duration)
        }

        return id
    }

    const showSuccess = (message, title = 'Success') => {
        return showNotification(message, { title, type: 'success', duration: 4000 })
    }

    const showError = (message, title = 'Error') => {
        return showNotification(message, { title, type: 'error', duration: 6000 })
    }

    const showWarning = (message, title = 'Warning') => {
        return showNotification(message, { title, type: 'warning', duration: 5000 })
    }

    const showInfo = (message, title = 'Info') => {
        return showNotification(message, { title, type: 'info', duration: 4000 })
    }

    const removeNotification = (id) => {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    return {
        notifications,
        showNotification,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        removeNotification,
    }
}
