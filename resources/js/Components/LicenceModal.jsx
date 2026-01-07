import React, { useState } from 'react';
import { router } from '@inertiajs/react';

/**
 * Modal component for adding licence or PPS code
 * 
 * @param {boolean} isOpen - Whether the modal is visible
 * @param {function} onClose - Function to call when modal is closed
 * @param {function} onSuccess - Function to call when licence/PPS is added successfully
 */
export default function LicenceModal({ isOpen, onClose, onSuccess }) {
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
                setError(data.message || 'Une erreur est survenue');
            }
        } catch (err) {
            setError('Une erreur est survenue lors de l\'ajout de la licence');
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
                setError(data.message || 'Une erreur est survenue');
            }
        } catch (err) {
            setError('Une erreur est survenue lors de l\'ajout du code PPS');
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
                            Licence ou Code PPS Requis
                        </h3>
                        <p className="mt-1 text-sm text-gray-500">
                            Pour vous inscrire à une course, vous devez avoir un numéro de licence valide ou un code PPS.
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
                            Licence (1 an)
                        </button>
                        <button
                            onClick={() => setActiveTab('pps')}
                            className={`flex-1 px-4 py-3 text-sm font-medium transition-colors ${
                                activeTab === 'pps'
                                    ? 'border-b-2 border-indigo-600 text-indigo-600'
                                    : 'text-gray-500 hover:text-gray-700'
                            }`}
                        >
                            Code PPS (3 mois)
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
                                        Numéro de Licence
                                    </label>
                                    <input
                                        type="text"
                                        id="licence_number"
                                        value={licenceNumber}
                                        onChange={(e) => setLicenceNumber(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Entrez votre numéro de licence"
                                        required
                                        disabled={loading}
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        Valide pendant 1 an à partir de la date d'ajout
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        disabled={loading}
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        disabled={loading}
                                    >
                                        {loading ? 'Ajout...' : 'Ajouter la Licence'}
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
                                        Code PPS
                                    </label>
                                    <input
                                        type="text"
                                        id="pps_code"
                                        value={ppsCode}
                                        onChange={(e) => setPpsCode(e.target.value)}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        placeholder="Entrez votre code PPS"
                                        required
                                        disabled={loading}
                                    />
                                    <p className="mt-2 text-sm text-gray-500">
                                        Valide pendant 3 mois à partir de la date d'ajout
                                    </p>
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={onClose}
                                        className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                        disabled={loading}
                                    >
                                        Annuler
                                    </button>
                                    <button
                                        type="submit"
                                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                                        disabled={loading}
                                    >
                                        {loading ? 'Ajout...' : 'Ajouter le Code PPS'}
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
