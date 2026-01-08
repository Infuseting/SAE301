import { useState } from 'react';

/**
 * Custom hook for managing image upload state
 * Works seamlessly with Inertia's useForm
 *
 * @param {string|null} initialImage - Initial image URL (for edit mode)
 * @returns {Object} Image upload state and handlers
 */
export function useImageUpload(initialImage = null) {
    const [preview, setPreview] = useState(initialImage);
    const [file, setFile] = useState(null);

    /**
     * Handle image change from ImageUpload component
     * @param {File|null} newFile - New file object
     * @param {string|null} previewUrl - Preview URL
     */
    const handleImageChange = (newFile, previewUrl) => {
        setFile(newFile);
        setPreview(previewUrl);
    };

    /**
     * Reset image state
     */
    const resetImage = () => {
        setFile(null);
        setPreview(initialImage);
    };

    /**
     * Check if image has changed
     * @returns {boolean}
     */
    const hasChanged = () => {
        return file !== null;
    };

    return {
        preview,
        file,
        handleImageChange,
        resetImage,
        hasChanged,
    };
}
