import React from 'react';

export default function UserAvatar({ user, className = "", src = null, alt = "" }) {
    const photoUrl = src || user.profile_photo_url;

    if (photoUrl) {
        return (
            <img
                className={`rounded-full object-cover ${className}`}
                src={photoUrl}
                alt={alt || user.name}
            />
        );
    }

    return (
        <div
            className={`rounded-full flex items-center justify-center bg-purple-600 text-white font-bold select-none ${className}`}
        >
            {user.name.charAt(0).toUpperCase()}
        </div>
    );
}
