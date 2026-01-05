import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';

/**
 * ProfileCompletionModal
 * 
 * A mandatory 2-step modal wizard that forces users to complete their profile information
 * if the `has_completed_profile` check fails.
 * 
 * Steps:
 * 1. Personal Information (DOB, Address, Phone)
 * 2. Licensing / Medical Information (License Number OR Medical Certificate Code)
 */
export default function ProfileCompletionModal() {
    const user = usePage().props.auth.user;
    const [isOpen, setIsOpen] = useState(false);
    const [step, setStep] = useState(1);

    // Check if profile is incomplete
    useEffect(() => {
        if (user && !user.has_completed_profile) {
            setIsOpen(true);
        } else {
            setIsOpen(false);
        }
    }, [user]);

    const { data, setData, post, processing, errors, reset } = useForm({
        birth_date: user.birth_date || '',
        address: user.address || '',
        phone: user.phone || '',
        license_number: user.license_number || '',
        medical_certificate: user.medical_certificate_code || '',
    });

    const nextStep = () => {
        // Basic client-side validation for step 1
        if (data.birth_date && data.address && data.phone) {
            setStep(2);
        } else {
            // Trigger browser validation or show error (simplified here)
            alert("Veuillez remplir toutes les informations personnelles.");
        }
    };

    const prevStep = () => {
        setStep(1);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('profile.complete'), {
            onSuccess: () => setIsOpen(false),
        });
    };

    if (!isOpen) return null;

    return (
        <Modal show={isOpen} maxWidth="lg">
            <div className="p-6">
                <h2 className="text-lg font-medium text-gray-900">
                    {step === 1 ? 'Finaliser votre inscription (1/2)' : 'Finaliser votre inscription (2/2)'}
                </h2>

                <p className="mt-1 text-sm text-gray-600 mb-6">
                    {step === 1
                        ? 'Veuillez compléter vos informations personnelles pour continuer.'
                        : 'Pour participer aux courses, nous avons besoin de votre numéro de licence ou d\'un certificat médical.'}
                </p>

                <form onSubmit={submit}>
                    {step === 1 && (
                        <div className="space-y-4">
                            <div>
                                <InputLabel htmlFor="birth_date" value="Date de naissance" />
                                <TextInput
                                    id="birth_date"
                                    type="date"
                                    className="mt-1 block w-full"
                                    value={data.birth_date}
                                    onChange={(e) => setData('birth_date', e.target.value)}
                                    required
                                />
                                <InputError message={errors.birth_date} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="address" value="Adresse complète" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    required
                                />
                                <InputError message={errors.address} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="phone" value="Numéro de téléphone" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    className="mt-1 block w-full"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                />
                                <InputError message={errors.phone} className="mt-2" />
                            </div>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="space-y-6">
                            <div>
                                <InputLabel htmlFor="license_number" value="Numéro de licence" />
                                <TextInput
                                    id="license_number"
                                    type="text"
                                    className={`mt-1 block w-full ${data.medical_certificate ? 'opacity-50 cursor-not-allowed bg-gray-100' : ''}`}
                                    value={data.license_number}
                                    onChange={(e) => setData(prev => ({ ...prev, license_number: e.target.value, medical_certificate: '' }))}
                                    disabled={!!data.medical_certificate}
                                    placeholder="ex: 12345678"
                                />
                                <InputError message={errors.license_number} className="mt-2" />
                            </div>

                            <div className="relative">
                                <div className="absolute inset-0 flex items-center" aria-hidden="true">
                                    <div className="w-full border-t border-gray-300"></div>
                                </div>
                                <div className="relative flex justify-center">
                                    <span className="bg-white px-2 text-sm text-gray-500 font-medium">OU</span>
                                </div>
                            </div>

                            <div>
                                <InputLabel htmlFor="medical_certificate" value="Code PPS / Certificat" />
                                <TextInput
                                    id="medical_certificate"
                                    type="text"
                                    className={`mt-1 block w-full ${data.license_number ? 'opacity-50 cursor-not-allowed bg-gray-100' : ''}`}
                                    value={data.medical_certificate}
                                    onChange={(e) => setData(prev => ({ ...prev, medical_certificate: e.target.value, license_number: '' }))}
                                    disabled={!!data.license_number}
                                    placeholder="ex: PPS-123456"
                                />
                                <InputError message={errors.medical_certificate} className="mt-2" />
                            </div>
                        </div>
                    )}

                    <div className="mt-6 flex justify-end gap-3">
                        {step === 2 && (
                            <SecondaryButton type="button" onClick={prevStep}>
                                Retour
                            </SecondaryButton>
                        )}

                        {step === 1 ? (
                            <PrimaryButton type="button" onClick={nextStep}>
                                Suivant
                            </PrimaryButton>
                        ) : (
                            <PrimaryButton disabled={processing} className="bg-emerald-600 hover:bg-emerald-500">
                                Terminer l'inscription
                            </PrimaryButton>
                        )}
                    </div>
                </form>
            </div>
        </Modal>
    );
}
