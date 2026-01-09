import React, { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

/**
 * Blocking modal that forces managers to add a valid licence
 * Cannot be closed until a valid licence is submitted
 * 
 * @param {boolean} show - Whether to show the modal
 */
export default function LicenceRequiredModal({ show }) {
    const { translations, auth } = usePage().props;
    const messages = translations?.messages || {};
    const currentUser = auth?.user;
    const t = (key, fallback) => translations?.profile?.[key] || fallback;
    
    const { data, setData, patch, processing, errors, reset } = useForm({
        first_name: currentUser?.first_name || '',
        last_name: currentUser?.last_name || '',
        email: currentUser?.email || '',
        birth_date: currentUser?.birth_date || '',
        address: currentUser?.address || '',
        phone: currentUser?.phone || '',
        license_number: '',
    });

    // Update form data when modal opens (only when show changes to true)
    useEffect(() => {
        if (show && currentUser) {
            setData({
                first_name: currentUser.first_name || '',
                last_name: currentUser.last_name || '',
                email: currentUser.email || '',
                birth_date: currentUser.birth_date || '',
                address: currentUser.address || '',
                phone: currentUser.phone || '',
                license_number: '',
            });
        }
    }, [show]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        patch(route('profile.update'), {
            preserveScroll: true,
            onSuccess: () => {
                // Modal will close automatically when requiresLicenceUpdate becomes false
                reset('license_number');
            },
        });
    };

    if (!show) return null;

    return (
        <div className="fixed inset-0 z-[9999] overflow-y-auto bg-black bg-opacity-75">
            <div className="flex min-h-screen items-center justify-center p-4">
                {/* Modal - Cannot be closed by clicking outside */}
                <div className="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-2xl">
                    {/* Warning Header */}
                    <div className="bg-red-600 px-6 py-4">
                        <div className="flex items-center">
                            <svg
                                className="h-8 w-8 text-white mr-3"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                                />
                            </svg>
                            <div>
                                <h3 className="text-xl font-bold text-white">
                                    {messages['modal.licence_required.title'] || 'Licence Required - Access Blocked'}
                                </h3>
                                <p className="mt-1 text-sm text-red-100">
                                    {messages['modal.licence_required.description'] || 'Your role requires a valid licence to access the application'}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="px-6 py-6">
                        <div className="mb-6 rounded-lg bg-yellow-50 border border-yellow-200 p-4">
                            <div className="flex">
                                <svg
                                    className="h-5 w-5 text-yellow-600 mr-2 flex-shrink-0"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fillRule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                        clipRule="evenodd"
                                    />
                                </svg>
                                <div className="text-sm text-yellow-800">
                                    <p className="font-semibold mb-1">{messages['modal.licence_required.why_title'] || 'Why this restriction?'}</p>
                                    <p>
                                        {messages['modal.licence_required.why_description'] || 'As a club member or event manager, you must have a valid licence to ensure compliance with the French Orienteering Federation (FFCO) regulations.'}
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Form */}
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <InputLabel htmlFor="license_number" value={messages['modal.licence_required.licence_label'] || 'Licence Number *'} />
                                <TextInput
                                    id="license_number"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.license_number}
                                    onChange={(e) => setData('license_number', e.target.value)}
                                    required
                                    autoFocus
                                    placeholder={messages['modal.licence_required.licence_placeholder'] || 'Ex: 123456 or AB12345'}
                                />
                                <InputError message={errors.license_number} className="mt-2" />
                                <p className="mt-1 text-xs text-gray-500">
                                    {messages['modal.licence_required.licence_hint'] || 'Your licence will be valid for 1 year from validation.'}
                                </p>
                            </div>

                            {/* Hidden required fields */}
                            <input type="hidden" name="first_name" value={data.first_name} />
                            <input type="hidden" name="last_name" value={data.last_name} />
                            <input type="hidden" name="email" value={data.email} />
                            <input type="hidden" name="birth_date" value={data.birth_date} />
                            <input type="hidden" name="address" value={data.address} />
                            <input type="hidden" name="phone" value={data.phone} />

                            <div className="flex justify-end pt-4 border-t border-gray-200">
                                <PrimaryButton disabled={processing || !data.license_number}>
                                    {processing ? (messages['modal.licence_required.validating'] || 'Validating...') : (messages['modal.licence_required.validate'] || 'Validate my Licence')}
                                </PrimaryButton>
                            </div>
                        </form>

                        <div className="mt-4 text-center">
                            <p className="text-xs text-gray-500">
                                {messages['modal.licence_required.cannot_close'] || '⚠️ This modal cannot be closed until you have added a valid licence.'}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
