import { useRef, useState } from 'react';
import { X, Image as ImageIcon, Upload } from 'lucide-react';

/**
 * Reusable Image Upload Component
 * Supports drag & drop, preview, and file validation
 *
 * @param {Object} props
 * @param {string} props.label - Label for the input
 * @param {string} props.name - Input name attribute
 * @param {Function} props.onChange - Callback when image changes (file, previewUrl)
 * @param {string} props.currentImage - URL of current image (for edit mode)
 * @param {string} props.error - Error message to display
 * @param {boolean} props.required - Whether the field is required
 * @param {string} props.accept - Accepted file types (default: image/*)
 * @param {number} props.maxSize - Max file size in MB (default: 5)
 * @param {string} props.className - Additional CSS classes
 * @param {string} props.helperText - Helper text to display
 */
export default function ImageUpload({
    label = 'Image',
    name = 'image',
    onChange,
    currentImage = null,
    error = null,
    required = false,
    accept = 'image/*',
    maxSize = 5,
    className = '',
    helperText = null,
}) {
    const [preview, setPreview] = useState(currentImage);
    const [isDragging, setIsDragging] = useState(false);
    const fileInputRef = useRef(null);

    /**
     * Handle file selection
     * @param {File} file - Selected file
     */
    const handleFile = (file) => {
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Veuillez sélectionner une image valide');
            return;
        }

        // Validate file size
        const fileSizeMB = file.size / (1024 * 1024);
        if (fileSizeMB > maxSize) {
            alert(`L'image ne doit pas dépasser ${maxSize}MB`);
            return;
        }

        // Create preview
        const reader = new FileReader();
        reader.onloadend = () => {
            const previewUrl = reader.result;
            setPreview(previewUrl);
            onChange?.(file, previewUrl);
        };
        reader.readAsDataURL(file);
    };

    /**
     * Handle file input change
     * @param {Event} e - Input change event
     */
    const handleInputChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    /**
     * Handle drag over
     * @param {DragEvent} e - Drag event
     */
    const handleDragOver = (e) => {
        e.preventDefault();
        setIsDragging(true);
    };

    /**
     * Handle drag leave
     * @param {DragEvent} e - Drag event
     */
    const handleDragLeave = (e) => {
        e.preventDefault();
        setIsDragging(false);
    };

    /**
     * Handle file drop
     * @param {DragEvent} e - Drop event
     */
    const handleDrop = (e) => {
        e.preventDefault();
        setIsDragging(false);

        const file = e.dataTransfer.files?.[0];
        if (file) {
            handleFile(file);
        }
    };

    /**
     * Remove selected image
     */
    const handleRemove = () => {
        setPreview(null);
        onChange?.(null, null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    /**
     * Open file dialog
     */
    const handleClick = () => {
        fileInputRef.current?.click();
    };

    return (
        <div className={className}>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {label}
                {required && <span className="text-red-500 ml-1">*</span>}
            </label>

            {helperText && (
                <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                    {helperText}
                </p>
            )}

            {/* Hidden file input */}
            <input
                ref={fileInputRef}
                type="file"
                name={name}
                accept={accept}
                onChange={handleInputChange}
                className="hidden"
            />

            {/* Preview or Upload Area */}
            {preview ? (
                <div className="relative group">
                    <img
                        src={preview}
                        alt="Aperçu"
                        className="w-full h-64 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600"
                    />
                    <div className="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-4">
                        <button
                            type="button"
                            onClick={handleClick}
                            className="p-3 bg-white dark:bg-gray-800 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            title="Changer l'image"
                        >
                            <Upload className="w-6 h-6 text-gray-700 dark:text-gray-300" />
                        </button>
                        <button
                            type="button"
                            onClick={handleRemove}
                            className="p-3 bg-white dark:bg-gray-800 rounded-full hover:bg-red-100 dark:hover:bg-red-900 transition-colors"
                            title="Supprimer l'image"
                        >
                            <X className="w-6 h-6 text-red-500" />
                        </button>
                    </div>
                </div>
            ) : (
                <div
                    onDragOver={handleDragOver}
                    onDragLeave={handleDragLeave}
                    onDrop={handleDrop}
                    onClick={handleClick}
                    className={`
                        border-2 border-dashed rounded-lg p-8 text-center cursor-pointer
                        transition-colors duration-200
                        ${isDragging
                            ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20'
                            : 'border-gray-300 dark:border-gray-600 hover:border-indigo-400 dark:hover:border-indigo-500'
                        }
                    `}
                >
                    <ImageIcon className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" />
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <span className="font-semibold text-indigo-600 dark:text-indigo-400">
                            Cliquez pour télécharger
                        </span>
                        {' '}ou glissez-déposez
                    </p>
                    <p className="text-xs text-gray-500 dark:text-gray-500">
                        PNG, JPG, GIF jusqu'à {maxSize}MB
                    </p>
                </div>
            )}

            {/* Error message */}
            {error && (
                <p className="mt-2 text-sm text-red-600 dark:text-red-400">
                    {error}
                </p>
            )}
        </div>
    );
}
