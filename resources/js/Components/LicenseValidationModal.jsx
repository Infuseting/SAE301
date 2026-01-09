import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';

/**
 * LicenseValidationModal
 * 
 * Modal displayed when an invalid FFCO license number is submitted.
 * Provides two options:
 * 1. "Changer" - Close modal and let user correct the license number
 * 2. "Plus tard" - Clear license number and submit form (user loses/doesn't get "adherent" role)
 * 
 * This modal is UNCLOSEABLE except via the two action buttons.
 * Protection: If removed from DOM, page interaction is blocked.
 */
export default function LicenseValidationModal({ show, onClose, onConfirmWithoutLicense }) {
    const messages = usePage().props.translations?.messages || {};
    const modalRef = useRef(null);
    const overlayRef = useRef(null);

    useEffect(() => {
        if (!show) {
            // Remove overlay if modal is closed properly
            if (overlayRef.current) {
                overlayRef.current.remove();
                overlayRef.current = null;
            }
            return;
        }

        // Create a blocking overlay if modal is shown
        const checkModalIntegrity = setInterval(() => {
            if (show && modalRef.current) {
                const modalInDom = document.body.contains(modalRef.current);
                
                if (!modalInDom) {
                    // Modal has been removed from DOM - create permanent blocking overlay
                    if (!overlayRef.current) {
                        const blockingOverlay = document.createElement('div');
                        blockingOverlay.id = 'license-validation-protection';
                        blockingOverlay.style.cssText = `
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background-color: rgba(0, 0, 0, 0.9);
                            z-index: 9999;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-size: 24px;
                            font-weight: bold;
                            text-align: center;
                            padding: 20px;
                        `;
                        blockingOverlay.innerHTML = `
                            <div>
                                <svg class="mx-auto h-16 w-16 text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p>${messages['modal.license_validation.unauthorized_action'] || "Action non autorisée détectée."}</p>
                                <p class="text-lg mt-2">${messages['modal.license_validation.reload_page'] || "Veuillez recharger la page."}</p>
                            </div>
                        `;
                        
                        // Make it unremovable
                        Object.defineProperty(blockingOverlay, 'remove', {
                            value: () => {},
                            writable: false,
                            configurable: false
                        });
                        
                        document.body.appendChild(blockingOverlay);
                        overlayRef.current = blockingOverlay;
                        
                        // Block all interactions
                        document.body.style.pointerEvents = 'none';
                        blockingOverlay.style.pointerEvents = 'auto';
                    }
                }
            }
        }, 100);

        return () => {
            clearInterval(checkModalIntegrity);
        };
    }, [show]);
    return (
        <Modal show={show} onClose={() => {}} closeable={false} maxWidth="md">
            <div className="p-6" ref={modalRef}>
                <div className="flex items-center gap-3 mb-4">
                    <div className="flex-shrink-0">
                        <svg className="h-10 w-10 text-orange-500" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                    <div>
                        <h3 className="text-lg font-semibold text-gray-900">
                            {messages['modal.license_validation.title'] || "Numéro de licence non conforme"}
                        </h3>
                    </div>
                </div>

                <div className="mb-6">
                    <p className="text-sm text-gray-700 mb-3">
                        {messages['modal.license_validation.invalid_message'] || "Le numéro de licence FFCO que vous avez saisi n'est pas valide."}
                    </p>
                    <p className="text-sm text-gray-600 mb-2">
                        {messages['modal.license_validation.expected_format'] || "Format attendu :"} <span className="font-mono font-medium">123456</span> {messages['or'] || "ou"} <span className="font-mono font-medium">AB12345</span>
                    </p>
                    <p className="text-sm text-gray-600">
                        {messages['modal.license_validation.format_explanation'] || "(5-6 chiffres ou 1-2 lettres suivies de 5-6 chiffres)"}
                    </p>
                </div>

                <div className="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                    <div className="flex gap-2">
                        <svg className="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fillRule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clipRule="evenodd" />
                        </svg>
                        <div className="text-sm text-amber-800">
                            <p className="font-medium mb-1">{messages['important'] || "Important"}</p>
                            <p>
                                {messages['modal.license_validation.warning'] || "Si vous continuez sans numéro de licence, vous ne pourrez pas obtenir (ou vous perdrez) le rôle"} <span className="font-semibold">{messages['modal.license_validation.adherent_role'] || "\"Adhérent\""}</span> {messages['modal.license_validation.features_access'] || "qui vous donne accès à certaines fonctionnalités."}
                            </p>
                        </div>
                    </div>
                </div>

                <div className="flex gap-3 justify-end">
                    <SecondaryButton onClick={onClose}>
                        {messages['modal.license_validation.change_number'] || "Changer le numéro"}
                    </SecondaryButton>
                    <DangerButton onClick={onConfirmWithoutLicense}>
                        {messages['modal.license_validation.continue_without'] || "Continuer sans licence"}
                    </DangerButton>
                </div>
            </div>
        </Modal>
    );
}
