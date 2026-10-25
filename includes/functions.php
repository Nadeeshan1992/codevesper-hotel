<?php
/**
 * Utility & Helper Functions
 */

function formatMoney($amount): string {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}

function formatDate($date, $format = 'M d, Y'): string {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

function formatDateTime($dateTime, $format = 'M d, Y h:i A'): string {
    if (!$dateTime) return '-';
    return date($format, strtotime($dateTime));
}

function sanitize($input): string {
    return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
}

function getRoomStatusBadge(string $status): string {
    switch (strtolower($status)) {
        case 'vacant':
            return '<span class="badge bg-success-subtle text-success border border-success"><i class="fa-solid fa-circle-check me-1"></i> Vacant & Clean</span>';
        case 'occupied':
            return '<span class="badge bg-danger-subtle text-danger border border-danger"><i class="fa-solid fa-user-check me-1"></i> Occupied</span>';
        case 'reserved':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning"><i class="fa-solid fa-calendar-check me-1"></i> Reserved</span>';
        case 'dirty':
            return '<span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary"><i class="fa-solid fa-broom me-1"></i> Housekeeping</span>';
        case 'maintenance':
            return '<span class="badge bg-dark-subtle text-dark border border-dark"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Maintenance</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

function getTableStatusBadge(string $status): string {
    switch (strtolower($status)) {
        case 'available':
            return '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-check me-1"></i> Available</span>';
        case 'occupied':
            return '<span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-utensils me-1"></i> Occupied</span>';
        case 'reserved':
            return '<span class="badge bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-clock me-1"></i> Reserved</span>';
        case 'cleaning':
            return '<span class="badge bg-info-subtle text-info"><i class="fa-solid fa-spray-can me-1"></i> Cleaning</span>';
        default:
            return '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>';
    }
}

function getOrderStatusBadge(string $status): string {
    switch (strtolower($status)) {
        case 'pending':
            return '<span class="badge bg-secondary-subtle text-secondary"><i class="fa-regular fa-clock me-1"></i> Pending</span>';
        case 'in_kitchen':
            return '<span class="badge bg-warning-subtle text-warning-emphasis"><i class="fa-solid fa-fire me-1"></i> In Kitchen</span>';
        case 'ready':
            return '<span class="badge bg-info-subtle text-info"><i class="fa-solid fa-bell-concierge me-1"></i> Ready to Serve</span>';
        case 'served':
            return '<span class="badge bg-primary-subtle text-primary"><i class="fa-solid fa-check me-1"></i> Served</span>';
        case 'billed':
            return '<span class="badge bg-success-subtle text-success"><i class="fa-solid fa-receipt me-1"></i> Billed</span>';
        case 'cancelled':
            return '<span class="badge bg-danger-subtle text-danger"><i class="fa-solid fa-xmark me-1"></i> Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

function getRoleBadge(string $roleSlug): string {
    switch ($roleSlug) {
        case 'admin':
            return '<span class="badge bg-primary"><i class="fa-solid fa-shield-halved me-1"></i> Admin</span>';
        case 'owner':
            return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-crown me-1"></i> Owner</span>';
        case 'reception':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-concierge-bell me-1"></i> Reception</span>';
        case 'waiter':
            return '<span class="badge bg-success"><i class="fa-solid fa-tablet-screen-button me-1"></i> Waiter (POS)</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($roleSlug) . '</span>';
    }
}
