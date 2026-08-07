import re

# Read the existing CSS
with open('app.css', 'r') as f:
    css = f.read()

# Modern Sidebar CSS to add
sidebar_css = """
/* =============================================
   MODERN SIDEBAR - Design System
   ============================================= */
@layer components {
    /* Sidebar Container - Glassmorphism */
    .modern-sidebar {
        @apply fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full transform flex-col;
        background: linear-gradient(180deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-right: 1px solid rgba(255, 255, 255, 0.05);
    }

    .modern-sidebar--open {
        @apply translate-x-0;
    }

    /* Sidebar Overlay for Mobile */
    .modern-sidebar-overlay {
        @apply fixed inset-0 z-30;
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }

    .modern-sidebar-overlay--visible {
        opacity: 1;
        visibility: visible;
    }

    /* Logo Section */
    .modern-sidebar__logo {
        @apply flex h-20 items-center gap-3 px-5;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .modern-sidebar__logo-mark {
        @apply relative flex h-11 w-11 shrink-0 items-center justify-center rounded-xl;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
    }

    .modern-sidebar__logo-mark::after {
        content: '';
        @apply absolute inset-0 rounded-xl;
        background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, transparent 50%);
    }

    .modern-sidebar__brand {
        @apply text-lg font-bold text-white;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }

    /* Navigation Container */
    .modern-sidebar__nav {
        @apply flex-1 overflow-y-auto py-4 px-3;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.1) transparent;
    }

    .modern-sidebar__nav::-webkit-scrollbar {
        width: 4px;
    }

    .modern-sidebar__nav::-webkit-scrollbar-track {
        background: transparent;
    }

    .modern-sidebar__nav::-webkit-scrollbar-thumb {
        background: rgba(255,255,255,0.1);
        border-radius: 2px;
    }

    /* Navigation Section Label */
    .modern-sidebar__section-label {
        @apply px-4 py-2 text-xs font-semibold uppercase tracking-wider;
        color: rgba(148, 163, 184, 0.6);
    }

    /* Navigation Item - Large Touch Target */
    .modern-nav-item {
        @apply relative flex items-center gap-3 px-4 py-3.5 my-1 rounded-xl text-sm font-medium;
        color: rgba(226, 232, 240, 0.8);
        min-height: 48px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .modern-nav-item:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(4px);
    }

    .modern-nav-item:active {
        transform: scale(0.98);
    }

    /* Active State - Glassmorphism Effect */
    .modern-nav-item--active {
        color: #ffffff;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(139, 92, 246, 0.3) 100%);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(99, 102, 241, 0.3);
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.2);
    }

    .modern-nav-item--active::before {
        content: '';
        @apply absolute left-0 top-1/2 -translate-y-1/2 w-1 rounded-r;
        background: linear-gradient(180deg, #6366f1 0%, #a855f7 100%);
        height: 60%;
        box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
    }

    /* Nav Item Icon */
    .modern-nav-item__icon {
        @apply relative flex h-10 w-10 shrink-0 items-center justify-center rounded-lg;
        transition: all 0.2s ease;
    }

    .modern-nav-item:hover .modern-nav-item__icon {
        background: rgba(255, 255, 255, 0.1);
    }

    .modern-nav-item--active .modern-nav-item__icon {
        background: rgba(99, 102, 241, 0.4);
    }

    .modern-nav-item__icon svg {
        @apply h-5 w-5;
    }

    /* Nav Item - Sub (Quick Access Classes) */
    .modern-nav-item--sub {
        @apply py-2.5 px-4 my-0.5 text-sm font-normal;
        color: rgba(148, 163, 184, 0.7);
        min-height: 40px;
    }

    .modern-nav-item--sub:hover {
        color: rgba(226, 232, 240, 0.9);
        background: rgba(255, 255, 255, 0.05);
        transform: translateX(2px);
    }

    .modern-nav-item--sub .modern-nav-item__icon {
        @apply h-8 w-8;
    }

    .modern-nav-item--sub .modern-nav-item__icon svg {
        @apply h-4 w-4;
    }

    /* Status Dot */
    .status-dot {
        @apply h-2.5 w-2.5 shrink-0 rounded-full;
    }

    .status-dot--emerald {
        background: #10b981;
        box-shadow: 0 0 8px rgba(16, 185, 129, 0.6);
    }

    .status-dot--rose {
        background: #f43f5e;
        animation: pulse-dot 2s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { box-shadow: 0 0 0 0 rgba(244, 63, 94, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(244, 63, 94, 0); }
    }

    /* Quick Access Cards Section */
    .quick-access-section {
        @apply my-3 mx-2 p-3 rounded-xl;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .quick-access-section__title {
        @apply flex items-center gap-2 text-xs font-semibold uppercase tracking-wider mb-2;
        color: rgba(148, 163, 184, 0.5);
    }

    .quick-access-card {
        @apply flex items-center gap-2.5 py-2 px-2.5 my-1 rounded-lg text-sm;
        color: rgba(226, 232, 240, 0.7);
        transition: all 0.2s ease;
    }

    .quick-access-card:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.06);
    }

    .quick-access-card__icon {
        @apply flex h-7 w-7 shrink-0 items-center justify-center rounded-md;
        background: rgba(99, 102, 241, 0.2);
        color: #818cf8;
    }

    .quick-access-card:hover .quick-access-card__icon {
        background: rgba(99, 102, 241, 0.3);
    }

    /* User Section */
    .modern-sidebar__user {
        @apply relative border-t p-4;
        border-color: rgba(255, 255, 255, 0.1);
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.5) 0%, rgba(15, 23, 42, 1) 100%);
    }

    .modern-sidebar__user-info {
        @apply flex items-center gap-3;
    }

    /* Avatar with Ring */
    .modern-avatar {
        @apply relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 4px 10px rgba(0, 0, 0, 0.3);
        transition: all 0.2s ease;
    }

    .modern-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.4), 0 6px 15px rgba(0, 0, 0, 0.4);
    }

    .modern-avatar__status {
        @apply absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2;
        border-color: #0f172a;
    }

    .modern-avatar__status--online {
        background: #10b981;
        box-shadow: 0 0 6px rgba(16, 185, 129, 0.6);
    }

    .modern-avatar__status--offline {
        background: #64748b;
    }

    .modern-sidebar__user-details {
        @apply min-w-0 flex-1;
    }

    .modern-sidebar__user-name {
        @apply block truncate text-sm font-semibold text-white;
        transition: color 0.2s ease;
    }

    .modern-sidebar__user-name:hover {
        color: #c7d2fe;
    }

    .modern-sidebar__user-school {
        @apply block truncate text-xs;
        color: rgba(148, 163, 184, 0.6);
    }

    /* Logout Button */
    .modern-sidebar__logout {
        @apply mt-3 flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm;
        color: rgba(148, 163, 184, 0.7);
        transition: all 0.2s ease;
    }

    .modern-sidebar__logout:hover {
        color: #f43f5e;
        background: rgba(244, 63, 94, 0.1);
    }

    .modern-sidebar__logout svg {
        @apply h-4 w-4;
    }

    /* WhatsApp Status Badge */
    .whatsapp-status {
        @apply inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium;
    }

    .whatsapp-status--connected {
        background: rgba(16, 185, 129, 0.15);
        color: #34d399;
    }

    .whatsapp-status--disconnected {
        background: rgba(244, 63, 94, 0.15);
        color: #fb7185;
    }
}

/* =============================================
   MOBILE RESPONSIVE SIDEBAR
   ============================================= */
@media (max-width: 1023px) {
    .modern-sidebar {
        width: 85%;
        max-width: 320px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .modern-sidebar__nav {
        /* Hide scrollbar on mobile */
        -ms-overflow-style: none;
    }

    .modern-sidebar__nav::-webkit-scrollbar {
        display: none;
    }
}

/* =============================================
   TOUCH SWIPE SUPPORT
   ============================================= */
@layer utilities {
    .touch-swipe-close {
        touch-action: pan-y;
    }
}

/* =============================================
   PRINT STYLES
   ============================================= */
@media print {
    .modern-sidebar,
    .modern-sidebar-overlay {
        display: none !important;
    }
}
"""

# Find the SIDEBAR section and replace it
sidebar_pattern = r'/\* =============================================\s+SIDEBAR\s+============================================= \*/.*?(?=/\*|$)'
if re.search(sidebar_pattern, css, re.DOTALL):
    css = re.sub(sidebar_pattern, sidebar_css.strip(), css, flags=re.DOTALL)
    print("Replaced SIDEBAR section")
else:
    # Append at the end
    css += sidebar_css
    print("Appended SIDEBAR section")

# Write the updated CSS
with open('app.css', 'w') as f:
    f.write(css)

print("CSS updated successfully!")
