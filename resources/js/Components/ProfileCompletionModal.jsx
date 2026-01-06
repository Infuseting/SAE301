import Modal from '@/Components/Modal';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { createPortal } from 'react-dom';

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

    const { data, setData, post, processing, errors } = useForm({
        birth_date: user.birth_date || '',
        address: user.address || '',
        phone: user.phone || '',
        license_number: user.license_number || '',
        medical_certificate_code: user.medical_certificate_code || '',
    });

    const nextStep = () => setStep((prev) => prev + 1);
    const prevStep = () => setStep((prev) => prev - 1);

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
                    {step === 1 ? 'Finaliser votre inscription (1/2)' : 'Informations complémentaires (2/2)'}
                </h2>

                <p className="mt-1 text-sm text-gray-600 mb-6">
                    {step === 1
                        ? "Veuillez compléter vos informations personnelles pour continuer."
                        : "Ces informations sont facultatives mais nécessaires pour certaines compétitions."}
                </p>

                <form onSubmit={submit}>
                    {step === 1 && (
                        <div className="space-y-4">
                            <div>
                                <InputLabel htmlFor="birth_date" value="Date de naissance" />
                                <DatePicker
                                    id="birth_date"
                                    selected={data.birth_date ? new Date(data.birth_date) : null}
                                    onChange={(date) => setData('birth_date', date ? date.toISOString().split('T')[0] : '')}
                                    className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    dateFormat="dd/MM/yyyy"
                                    placeholderText="JJ/MM/AAAA"
                                    required
                                    showYearDropdown
                                    dropdownMode="select"
                                    popperContainer={({ children }) => createPortal(children, document.body)}
                                    popperPlacement="bottom-start"
                                    popperClassName="!z-[100]"
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

                            <div className="mt-6 flex justify-end">
                                <PrimaryButton type="button" onClick={nextStep} className="bg-emerald-600 hover:bg-emerald-500">
                                    Suivant
                                </PrimaryButton>
                            </div>
                        </div>
                    )}

                    {step === 2 && (
                        <div className="space-y-4">
                            <div>
                                <InputLabel htmlFor="license_number" value="Numéro de licence (Facultatif)" />
                                <TextInput
                                    id="license_number"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.license_number}
                                    onChange={(e) => setData('license_number', e.target.value)}
                                />
                                <InputError message={errors.license_number} className="mt-2" />
                            </div>

                            <div className="relative flex py-1 items-center">
                                <div className="flex-grow border-t border-gray-200"></div>
                                <span className="flex-shrink-0 mx-4 text-gray-400 text-xs uppercase font-bold">OU</span>
                                <div className="flex-grow border-t border-gray-200"></div>
                            </div>

                            <div>
                                <InputLabel htmlFor="medical_certificate" value="Code PPS (Parcours Prévention Santé)" />
                                <TextInput
                                    id="medical_certificate"
                                    type="text"
                                    className="mt-1 block w-full"
                                    value={data.medical_certificate_code}
                                    onChange={(e) => setData('medical_certificate_code', e.target.value)}
                                />
                                <InputError message={errors.medical_certificate_code} className="mt-2" />
                            </div>

                            <div className="mt-6 flex justify-between items-center">
                                <button
                                    type="button"
                                    onClick={prevStep}
                                    className="text-sm text-gray-600 underline hover:text-gray-900"
                                >
                                    Retour
                                </button>
                                <div className="flex gap-2">
                                    <SecondaryButton type="submit" disabled={processing} onClick={() => {
                                        setData('license_number', '');
                                        setData('medical_certificate_code', '');
                                    }}>
                                        Passer
                                    </SecondaryButton>
                                    <PrimaryButton disabled={processing} className="bg-emerald-600 hover:bg-emerald-500">
                                        Terminer
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    )}
                </form>
            </div>
        </Modal>
    );
}
