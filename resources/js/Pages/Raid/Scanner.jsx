import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState, useEffect, useRef } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { QrCode, Camera, CheckCircle, XCircle, Users, TrendingUp, AlertCircle, Trophy, Loader } from 'lucide-react';

/**
 * QR Code Scanner Component for Raid Check-in
 * Allows raid managers to scan team QR codes and mark them as present
 */
export default function Scanner({ raid, stats }) {
    const [isScanning, setIsScanning] = useState(false);
    const [scanResult, setScanResult] = useState(null);
    const [error, setError] = useState(null);
    const [loading, setLoading] = useState(false);
    const scannerRef = useRef(null);
    const html5QrCodeRef = useRef(null);

    useEffect(() => {
        return () => {
            // Cleanup scanner on unmount
            if (html5QrCodeRef.current && isScanning) {
                html5QrCodeRef.current.stop().catch(err => console.error('Error stopping scanner:', err));
            }
        };
    }, [isScanning]);

    const startScanning = async () => {
        try {
            setError(null);
            setScanResult(null);
            
            const html5QrCode = new Html5Qrcode("qr-reader");
            html5QrCodeRef.current = html5QrCode;

            await html5QrCode.start(
                { facingMode: "environment" }, // Use back camera on mobile
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                },
                onScanSuccess,
                onScanError
            );

            setIsScanning(true);
        } catch (err) {
            console.error('Error starting scanner:', err);
            setError('Impossible de démarrer la caméra. Vérifiez les permissions.');
        }
    };

    const stopScanning = async () => {
        if (html5QrCodeRef.current && isScanning) {
            try {
                await html5QrCodeRef.current.stop();
                html5QrCodeRef.current = null;
                setIsScanning(false);
            } catch (err) {
                console.error('Error stopping scanner:', err);
            }
        }
    };

    const onScanSuccess = async (decodedText) => {
        // Stop scanning temporarily
        await stopScanning();
        setLoading(true);

        try {
            // Parse QR code data
            const qrData = JSON.parse(decodedText);
            
            if (qrData.type !== 'team_registration') {
                setError('QR Code invalide. Ce n\'est pas un code d\'inscription d\'équipe.');
                setLoading(false);
                return;
            }

            // Send check-in request
            const response = await fetch(route('raids.check-in', raid.raid_id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    equ_id: qrData.equ_id,
                    reg_id: qrData.reg_id,
                }),
            });

            const data = await response.json();

            if (data.success) {
                setScanResult({
                    success: true,
                    message: data.message,
                    registration: data.registration,
                    alreadyPresent: data.already_present || false,
                });

                // Reload page after 3 seconds to update stats
                setTimeout(() => {
                    router.reload({ only: ['stats'] });
                }, 3000);
            } else {
                setError(data.message || 'Erreur lors de l\'enregistrement de la présence.');
            }
        } catch (err) {
            console.error('Error processing QR code:', err);
            setError('Erreur lors du traitement du QR Code. Assurez-vous qu\'il s\'agit d\'un code valide.');
        } finally {
            setLoading(false);
        }
    };

    const onScanError = (errorMessage) => {
        // Ignore scan errors (they happen continuously while scanning)
    };

    const resetScanner = () => {
        setScanResult(null);
        setError(null);
        startScanning();
    };

    const progressPercentage = stats.total > 0 ? Math.round((stats.present / stats.total) * 100) : 0;

    return (
        <AuthenticatedLayout>
            <Head title={`Scanner - ${raid.raid_name}`} />

            <div className="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 py-8">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="bg-white rounded-2xl shadow-lg p-6 mb-6 border-2 border-emerald-100">
                        <div className="flex items-start justify-between">
                            <div>
                                <div className="flex items-center gap-3 mb-2">
                                    <Trophy className="w-8 h-8 text-emerald-600" />
                                    <h1 className="text-3xl font-black text-gray-900">
                                        {raid.raid_name}
                                    </h1>
                                </div>
                                <p className="text-gray-600">
                                    Scanner QR Code - Pointage des équipes
                                </p>
                                <p className="text-sm text-gray-500 mt-1">
                                    {new Date(raid.raid_date_start).toLocaleDateString('fr-FR', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                    })}
                                </p>
                            </div>
                            <div className="text-right">
                                <div className="text-4xl font-black text-emerald-600">
                                    {progressPercentage}%
                                </div>
                                <div className="text-sm text-gray-500">
                                    Présence
                                </div>
                            </div>
                        </div>

                        {/* Stats */}
                        <div className="grid grid-cols-3 gap-4 mt-6">
                            <div className="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-4 border-2 border-blue-200">
                                <div className="flex items-center gap-2 mb-2">
                                    <Users className="w-5 h-5 text-blue-600" />
                                    <span className="text-xs font-semibold text-blue-700 uppercase">Total</span>
                                </div>
                                <div className="text-3xl font-black text-blue-900">{stats.total}</div>
                            </div>
                            <div className="bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl p-4 border-2 border-emerald-200">
                                <div className="flex items-center gap-2 mb-2">
                                    <CheckCircle className="w-5 h-5 text-emerald-600" />
                                    <span className="text-xs font-semibold text-emerald-700 uppercase">Présents</span>
                                </div>
                                <div className="text-3xl font-black text-emerald-900">{stats.present}</div>
                            </div>
                            <div className="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-4 border-2 border-amber-200">
                                <div className="flex items-center gap-2 mb-2">
                                    <AlertCircle className="w-5 h-5 text-amber-600" />
                                    <span className="text-xs font-semibold text-amber-700 uppercase">Absents</span>
                                </div>
                                <div className="text-3xl font-black text-amber-900">{stats.absent}</div>
                            </div>
                        </div>

                        {/* Progress Bar */}
                        <div className="mt-6">
                            <div className="bg-gray-200 rounded-full h-4 overflow-hidden">
                                <div
                                    className="bg-gradient-to-r from-emerald-500 to-emerald-600 h-full transition-all duration-500 rounded-full"
                                    style={{ width: `${progressPercentage}%` }}
                                />
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Scanner Section */}
                        <div className="bg-white rounded-2xl shadow-lg p-6 border-2 border-emerald-100">
                            <div className="flex items-center justify-between mb-6">
                                <h2 className="text-xl font-black text-gray-900 flex items-center gap-2">
                                    <QrCode className="w-6 h-6 text-emerald-600" />
                                    Scanner QR Code
                                </h2>
                            </div>

                            {/* Scanner Container */}
                            <div className="relative">
                                {!isScanning && !scanResult && !error && (
                                    <div className="bg-gradient-to-br from-gray-100 to-gray-200 rounded-xl p-12 text-center border-2 border-dashed border-gray-300">
                                        <Camera className="w-16 h-16 mx-auto mb-4 text-gray-400" />
                                        <p className="text-gray-600 mb-6">
                                            Cliquez sur le bouton ci-dessous pour activer la caméra
                                        </p>
                                        <button
                                            onClick={startScanning}
                                            className="bg-emerald-600 text-white px-8 py-4 rounded-xl font-bold hover:bg-emerald-700 transition shadow-lg flex items-center gap-2 mx-auto"
                                        >
                                            <Camera className="w-5 h-5" />
                                            Démarrer le scanner
                                        </button>
                                    </div>
                                )}

                                {isScanning && (
                                    <div className="space-y-4">
                                        <div id="qr-reader" className="rounded-xl overflow-hidden border-4 border-emerald-500 shadow-2xl" />
                                        <button
                                            onClick={stopScanning}
                                            className="w-full bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition"
                                        >
                                            Arrêter le scanner
                                        </button>
                                    </div>
                                )}

                                {loading && (
                                    <div className="bg-blue-50 rounded-xl p-12 text-center border-2 border-blue-200">
                                        <Loader className="w-12 h-12 mx-auto mb-4 text-blue-600 animate-spin" />
                                        <p className="text-blue-700 font-semibold">
                                            Vérification en cours...
                                        </p>
                                    </div>
                                )}

                                {scanResult && (
                                    <div className={`rounded-xl p-6 border-2 ${
                                        scanResult.alreadyPresent 
                                            ? 'bg-amber-50 border-amber-200' 
                                            : 'bg-emerald-50 border-emerald-200'
                                    }`}>
                                        <div className="flex items-start gap-4">
                                            {scanResult.alreadyPresent ? (
                                                <AlertCircle className="w-12 h-12 text-amber-600 flex-shrink-0" />
                                            ) : (
                                                <CheckCircle className="w-12 h-12 text-emerald-600 flex-shrink-0" />
                                            )}
                                            <div className="flex-1">
                                                <h3 className={`text-xl font-bold mb-2 ${
                                                    scanResult.alreadyPresent ? 'text-amber-900' : 'text-emerald-900'
                                                }`}>
                                                    {scanResult.message}
                                                </h3>
                                                <div className="space-y-1 text-sm text-gray-700">
                                                    <p><strong>Équipe:</strong> {scanResult.registration.team_name}</p>
                                                    <p><strong>Course:</strong> {scanResult.registration.race_name}</p>
                                                    {scanResult.registration.reg_dossard && (
                                                        <p><strong>Dossard:</strong> {scanResult.registration.reg_dossard}</p>
                                                    )}
                                                    {scanResult.registration.leader_name && (
                                                        <p><strong>Capitaine:</strong> {scanResult.registration.leader_name}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            onClick={resetScanner}
                                            className="w-full mt-4 bg-white text-emerald-700 border-2 border-emerald-300 px-6 py-3 rounded-xl font-bold hover:bg-emerald-50 transition"
                                        >
                                            Scanner un autre QR Code
                                        </button>
                                    </div>
                                )}

                                {error && (
                                    <div className="bg-red-50 rounded-xl p-6 border-2 border-red-200">
                                        <div className="flex items-start gap-4">
                                            <XCircle className="w-12 h-12 text-red-600 flex-shrink-0" />
                                            <div className="flex-1">
                                                <h3 className="text-xl font-bold text-red-900 mb-2">
                                                    Erreur
                                                </h3>
                                                <p className="text-red-700">{error}</p>
                                            </div>
                                        </div>
                                        <button
                                            onClick={resetScanner}
                                            className="w-full mt-4 bg-white text-red-700 border-2 border-red-300 px-6 py-3 rounded-xl font-bold hover:bg-red-50 transition"
                                        >
                                            Réessayer
                                        </button>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Instructions Section */}
                        <div className="bg-white rounded-2xl shadow-lg p-6 border-2 border-emerald-100">
                            <h2 className="text-xl font-black text-gray-900 mb-6 flex items-center gap-2">
                                <AlertCircle className="w-6 h-6 text-emerald-600" />
                                Instructions
                            </h2>

                            <div className="space-y-4">
                                <div className="flex items-start gap-3 p-4 bg-emerald-50 rounded-xl border border-emerald-200">
                                    <div className="w-8 h-8 bg-emerald-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">
                                        1
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-gray-900 mb-1">Activer la caméra</h3>
                                        <p className="text-sm text-gray-600">
                                            Cliquez sur "Démarrer le scanner" et autorisez l'accès à la caméra
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 p-4 bg-blue-50 rounded-xl border border-blue-200">
                                    <div className="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">
                                        2
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-gray-900 mb-1">Scanner le QR Code</h3>
                                        <p className="text-sm text-gray-600">
                                            Placez le QR Code du ticket d'équipe devant la caméra
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 p-4 bg-purple-50 rounded-xl border border-purple-200">
                                    <div className="w-8 h-8 bg-purple-600 text-white rounded-full flex items-center justify-center font-bold flex-shrink-0">
                                        3
                                    </div>
                                    <div>
                                        <h3 className="font-bold text-gray-900 mb-1">Confirmation automatique</h3>
                                        <p className="text-sm text-gray-600">
                                            L'équipe est automatiquement marquée comme présente
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 bg-amber-50 border-2 border-amber-200 rounded-xl p-4">
                                <div className="flex items-start gap-3">
                                    <AlertCircle className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" />
                                    <div>
                                        <h4 className="font-bold text-amber-900 mb-1">Points importants</h4>
                                        <ul className="text-sm text-amber-800 space-y-1">
                                            <li>• Vérifiez que tous les membres de l'équipe sont présents</li>
                                            <li>• Le QR Code doit être sur le ticket officiel</li>
                                            <li>• Chaque équipe ne peut être scannée qu'une seule fois</li>
                                            <li>• Les statistiques se mettent à jour automatiquement</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
