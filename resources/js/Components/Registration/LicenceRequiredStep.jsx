import React, { useState } from 'react';
import { FileCheck, CreditCard, ExternalLink, AlertCircle } from 'lucide-react';

/**
 * Licence/PPS step for users without valid credentials.
 * Allows entering licence number or PPS code.
 * 
 * @param {object} user - Current user
 * @param {object} translations - Translation strings
 * @param {function} onNext - Handler for proceeding to next step
 */
export default function LicenceRequiredStep({ user, translations, onNext }) {
    const [mode, setMode] = useState('licence'); // 'licence' or 'pps'
    const [value, setValue] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        const endpoint = mode === 'licence' ? '/licence' : '/pps';
        const fieldName = mode === 'licence' ? 'licence_number' : 'pps_code';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ [fieldName]: value }),
            });

            const data = await response.json();

            if (response.ok) {
                setSuccess(true);
                setTimeout(() => onNext(), 1000);
            } else {
                setError(data.message || data.errors?.[fieldName]?.[0] || 'Erreur de validation');
            }
        } catch (err) {
            setError('Erreur lors de l\'enregistrement');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="space-y-6">
            {/* Info banner */}
            <div className="p-4 bg-amber-50 border border-amber-100 rounded-2xl flex items-start gap-3">
                <AlertCircle className="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" />
                <div className="text-sm">
                    <p className="font-bold text-amber-800">
                        {translations.licence_required_title || 'Licence ou Code PPS Requis'}
                    </p>
                    <p className="text-amber-700 mt-1">
                        {translations.licence_required_text || 'Pour vous inscrire à une course, vous devez avoir un numéro de licence valide ou un code PPS.'}
                    </p>
                </div>
            </div>

            {/* Toggle buttons */}
            <div className="flex bg-slate-100 rounded-2xl p-1">
                <button
                    type="button"
                    onClick={() => setMode('licence')}
                    className={`flex-1 py-3 px-4 rounded-xl font-bold text-sm uppercase tracking-wide transition-all ${mode === 'licence'
                            ? 'bg-white text-blue-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        }`}
                >
                    <CreditCard className="inline h-4 w-4 mr-2" />
                    Licence
                </button>
                <button
                    type="button"
                    onClick={() => setMode('pps')}
                    className={`flex-1 py-3 px-4 rounded-xl font-bold text-sm uppercase tracking-wide transition-all ${mode === 'pps'
                            ? 'bg-white text-blue-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700'
                        }`}
                >
                    <FileCheck className="inline h-4 w-4 mr-2" />
                    Code PPS
                </button>
            </div>

            {/* Success message */}
            {success && (
                <div className="p-4 bg-emerald-50 border border-emerald-100 rounded-2xl text-sm text-emerald-700 font-medium text-center">
                    ✓ {mode === 'licence' ? 'Licence ajoutée' : 'Code PPS ajouté'} avec succès !
                </div>
            )}

            {/* Error message */}
            {error && (
                <div className="p-4 bg-red-50 border border-red-100 rounded-2xl text-sm text-red-700">
                    {error}
                </div>
            )}

            {/* Form */}
            {!success && (
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
                            {mode === 'licence'
                                ? (translations.licence_number || 'Numéro de Licence')
                                : (translations.pps_code || 'Code PPS')
                            }
                        </label>
                        <input
                            type="text"
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            placeholder={mode === 'licence' ? 'Ex: 2468013' : 'Ex: PPS-123456'}
                            className="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition-all"
                            required
                        />
                        <p className="mt-2 text-xs text-slate-400">
                            {mode === 'licence'
                                ? (translations.licence_valid_for || 'Valide pendant 1 an')
                                : (translations.pps_valid_for || 'Valide pendant 3 mois')
                            }
                        </p>
                    </div>

                    {mode === 'pps' && (
                        <a
                            href="https://pps.athle.fr/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
                        >
                            <ExternalLink className="h-4 w-4" />
                            Obtenir un code PPS sur pps.athle.fr
                        </a>
                    )}

                    <button
                        type="submit"
                        disabled={loading || !value.trim()}
                        className="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm uppercase tracking-widest rounded-2xl shadow-lg shadow-blue-200 transition-all disabled:opacity-50"
                    >
                        {loading ? (
                            <span className="inline-block animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent" />
                        ) : (
                            translations.save || 'Enregistrer'
                        )}
                    </button>
                </form>
            )}
        </div>
    );
}
