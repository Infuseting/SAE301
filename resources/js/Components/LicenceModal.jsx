import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

/**
 * Modal component for adding licence or PPS code
 * 
 * @param {boolean} isOpen - Whether the modal is visible
 * @param {function} onClose - Function to call when modal is closed
 * @param {function} onSuccess - Function to call when licence/PPS is added successfully
 */
export default function LicenceModal({ isOpen, onClose, onSuccess }) {
    const messages = usePage().props.translations?.messages || {};
    const [activeTab, setActiveTab] = useState('licence');
    const [licenceNumber, setLicenceNumber] = useState('');
    const [ppsCode, setPpsCode] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmitLicence = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(route('licence.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ licence_number: licenceNumber }),
            });

            const data = await response.json();

            if (data.success) {
                setLicenceNumber('');
                if (onSuccess) onSuccess(data);
                onClose();
            } else {
                setError(data.message || (messages['modal.licence.error_occurred'] || 'An error occurred'));
            }
        } catch (err) {
            setError(messages['modal.licence.error_adding_licence'] || 'An error occurred while adding the licence');
        } finally {
            setLoading(false);
        }
    };

    const handleSubmitPps = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const response = await fetch(route('pps.store'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ pps_code: ppsCode }),
            });

            const data = await response.json();

            if (data.success) {
                setPpsCode('');
                if (onSuccess) onSuccess(data);
                onClose();
            } else {
                setError(data.message || (messages['modal.licence.error_occurred'] || 'An error occurred'));
            }
        } catch (err) {
            setError(messages['modal.licence.error_adding_pps'] || 'An error occurred while adding the PPS code');
        } finally {
            setLoading(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex min-h-screen items-center justify-center p-4">
                {/* Backdrop */}
                <div
                    className="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
                    onClick={onClose}
                />

                {/* Modal */}
                <div className="relative w-full max-w-md transform overflow-hidden rounded-lg bg-white shadow-xl transition-all">
                    {/* Header */}
                    <div className="border-b border-gray-200 px-6 py-4">
                        <h3 className="text-lg font-semibold text-gray-900">
                            {messages['modal.licence.title'] || 'Licence or PPS Code Required'}
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            {messages['modal.licence.description'] || 'To register for a race, you must have a valid licence number or PPS code.'}
                        </p>
                    </div>

                    {/* Tabs */}
                    <div className="flex border-b border-gray-200">
                        <button
                            onClick={() => setActiveTab('licence')}
                            className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
                                activeTab === 'licence'
                                    ? 'border-b-2 border-indigo-600 text-indigo-600'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            {messages['modal.licence.tab_licence'] || 'Licence (1 year)'}
                        </button>
                        <button
                            onClick={() => setActiveTab('pps')}
                            className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
                                activeTab === 'pps'
                                    ? 'border-b-2 border-indigo-600 text-indigo-600'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            {messages['modal.licence.tab_pps'] || 'PPS Code (3 months)'}
                        </button>
                    </div>

                    {/* Error message */}
                    {error && (
                        <div className="mx-6 mt-4 rounded-md bg-red-50 p-4">
                            <p className="text-sm text-red-800">{error}</p>
                        </div>
                    )}

                    {/* Content */}
                    <div className="px-6 py-4">
                        {activeTab === 'licence' ? (
                            <form onSubmit={handleSubmitLicence}>
                                <div className="mb-4">
                                    <label
                                        htmlFor="licence_number"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        {messages['modal.licence.licence_number'] || 'Licence Number'}
                                    </label>
                                    <input
                                        type="text"
                                        id="licence_number"
                                        value={licenceNumber}
                                        onChange={(e) => setLicenceNumber(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder={messages['modal.licence.licence_placeholder'] || 'Enter your licence number'}
                                        required
                                        disabled={loading}
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        {messages['modal.licence.licence_hint'] || 'Valid for 1 year from the date of addition'}
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        disabled={loading}
                                    >
                                        {messages['cancel'] || 'Cancel'}
                                    </button>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        disabled={loading}
                                    >
                                        {loading ? (messages['modal.licence.adding'] || 'Adding...') : (messages['modal.licence.add_licence'] || 'Add Licence')}
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <form onSubmit={handleSubmitPps}>
                                <div className="mb-4">
                                    <label
                                        htmlFor="pps_code"
                                        className="block text-sm font-medium text-gray-700"
                                    >
                                        {messages['modal.licence.pps_code'] || 'PPS Code'}
                                    </label>
                                    <input
                                        type="text"
                                        id="pps_code"
                                        value={ppsCode}
                                        onChange={(e) => setPpsCode(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder={messages['modal.licence.pps_placeholder'] || 'Enter your PPS code'}
                                        required
                                        disabled={loading}
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        {messages['modal.licence.pps_hint'] || 'Valid for 3 months from the date of addition'}
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        disabled={loading}
                                    >
                                        {messages['cancel'] || 'Cancel'}
                                    </button>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        disabled={loading}
                                    >
                                        {loading ? (messages['modal.licence.adding'] || 'Adding...') : (messages['modal.licence.add_pps'] || 'Add PPS Code')}
                                    </button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
