import React from 'react';
import { Users } from 'lucide-react';

/**
 * Avatar component that displays user/team profile photo, icon, or initials
 * @param {Object} user - User/Team object with name and profile_photo_url
 * @param {string} className - Additional CSS classes for the avatar
 * @param {string} src - Optional custom image source
 * @param {string} alt - Alt text for the image
 * @param {string} size - Size of the avatar (sm, md, lg, xl)
 * @param {string} type - Type of entity: 'user' or 'team' (default: 'user')
 * @returns {JSX.Element} Avatar component
 */
export default function UserAvatar({ user, className = "", src = null, alt = "", size = "md", type = "user" }) {
    const photoUrl = src || user?.profile_photo_url;
    
    // Get initials from user/team name (first letter of first name and last name)
    const getInitials = (name) => {
        if (!name) return '?';
        const parts = name.trim().split(' ');
        if (parts.length === 1) {
            return parts[0].charAt(0).toUpperCase();
        }
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    };

    // Size classes mapping
    const sizeClasses = {
        sm: 'h-8 w-8 text-xs',
        md: 'h-10 w-10 text-sm',
        lg: 'h-12 w-12 text-base',
        xl: 'h-16 w-16 text-lg'
    };

    // Icon size mapping for team default avatar
    const iconSizeClasses = {
        sm: 16,
        md: 20,
        lg: 24,
        xl: 32
    };

    const sizeClass = sizeClasses[size] || sizeClasses.md;
    const iconSize = iconSizeClasses[size] || iconSizeClasses.md;

    // Color schemes for different types
    const colorSchemes = {
        user: 'bg-gradient-to-br from-blue-400 to-blue-600',
        team: 'bg-gradient-to-br from-emerald-400 to-emerald-600'
    };

    const colorScheme = colorSchemes[type] || colorSchemes.user;

    if (photoUrl) {
        return (
            <img
                className={`rounded-full object-cover ${sizeClass} ${className}`}
                src={photoUrl}
                alt={alt || user?.name || `${type} avatar`}
            />
        );
    }

    // For teams without image, show team icon
    if (type === 'team') {
        return (
            <div
                className={`rounded-full flex items-center justify-center ${colorScheme} text-white select-none ${sizeClass} ${className}`}
            >
                <Users size={iconSize} />
            </div>
        );
    }

    // For users without image, show initials
    return (
        <div
            className={`rounded-full flex items-center justify-center ${colorScheme} text-white font-semibold select-none ${sizeClass} ${className}`}
        >
            {getInitials(user?.name)}
        </div>
    );
}
