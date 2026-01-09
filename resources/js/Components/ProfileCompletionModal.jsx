import Modal from '@/Components/Modal';
import DatePicker from "react-datepicker";
import "react-datepicker/dist/react-datepicker.css";
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import LicenseValidationModal from '@/Components/LicenseValidationModal';
import { useForm, usePage, router } from '@inertiajs/react';
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
    const messages = usePage().props.translations?.messages || {};
    const [isOpen, setIsOpen] = useState(false);
    const [showLicenseModal, setShowLicenseModal] = useState(false);
    const [selectedDate, setSelectedDate] = useState(null);

    // Check if profile is incomplete
    useEffect(() => {
        if (user && !user.has_completed_profile) {
            setIsOpen(true);
        } else {
            setIsOpen(false);
        }
    }, [user?.has_completed_profile]);

    const { data, setData, post, processing, errors, clearErrors } = useForm({
        birth_date: user.birth_date || '',
        address: user.address || '',
        phone: user.phone || '',
        license_number: user.license_number || '',
    });
    
    // Initialize selectedDate from user.birth_date only once when modal opens
    useEffect(() => {
        if (isOpen && user.birth_date && !selectedDate) {
            try {
                setSelectedDate(new Date(user.birth_date + 'T00:00:00'));
            } catch (e) {
                setSelectedDate(null);
            }
        }
    }, [isOpen, user.birth_date]);

    const submit = (e) => {
        e.preventDefault();
        post(route('profile.complete'), {
            onSuccess: () => setIsOpen(false),
            onError: (errors) => {
                // If there's a license number error, show the modal
                if (errors.license_number && data.license_number) {
                    setShowLicenseModal(true);
                }
            }
        });
    };

    const handleConfirmWithoutLicense = () => {
        setShowLicenseModal(false);
        clearErrors('license_number');
        
        // Use router.post directly with explicit data (bypasses useForm state)
        router.post(route('profile.complete'), {
            birth_date: data.birth_date,
            address: data.address,
            phone: data.phone,
            license_number: null, // Explicitly null, not empty string
        }, {
            onSuccess: () => {
                setIsOpen(false);
                setData('license_number', '');
            },
        });
    };

    if (!isOpen) return null;

    return (
        <>
            <LicenseValidationModal
                show={showLicenseModal}
                onClose={() => setShowLicenseModal(false)}
                onConfirmWithoutLicense={handleConfirmWithoutLicense}
            />
            
            <Modal show={isOpen} maxWidth="lg">
            <div className="p-6">
                <h2 className="text-lg font-medium text-gray-900">
                    {messages['modal.complete_profile.title'] || "Finaliser votre inscription"}
                </h2>

                <p className="mt-1 text-sm text-gray-600 mb-6">
                    {messages['modal.complete_profile.description'] || "Veuillez compléter vos informations personnelles pour continuer."}
                </p>

                <form onSubmit={submit}>
                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="birth_date" value={messages['modal.complete_profile.birth_date'] || "Date de naissance *"} />
                            <DatePicker
                                id="birth_date"
                                selected={selectedDate}
                                onChange={(date) => {
                                    setSelectedDate(date);
                                    if (date) {
                                        const year = date.getFullYear();
                                        const month = String(date.getMonth() + 1).padStart(2, '0');
                                        const day = String(date.getDate()).padStart(2, '0');
                                        setData('birth_date', `${year}-${month}-${day}`);
                                    } else {
                                        setData('birth_date', '');
                                    }
                                }}
                                className="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                dateFormat="dd/MM/yyyy"
                                placeholderText={messages['modal.complete_profile.date_placeholder'] || "JJ/MM/AAAA"}
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
                            <InputLabel htmlFor="address" value={messages['modal.complete_profile.address'] || "Adresse complète *"} />
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
                            <InputLabel htmlFor="phone" value={messages['modal.complete_profile.phone'] || "Numéro de téléphone *"} />
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

                        <div className="border-t pt-4 mt-4">
                            <InputLabel htmlFor="license_number" value={messages['modal.complete_profile.license_number'] || "Numéro de licence FFCO (Facultatif)"} />
                            <TextInput
                                id="license_number"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.license_number}
                                onChange={(e) => setData('license_number', e.target.value)}
                                placeholder={messages['modal.complete_profile.license_placeholder'] || "Ex: 123456 ou AB12345"}
                            />
                            <p className="mt-1 text-xs text-gray-500">{messages['modal.complete_profile.license_format'] || "Format : 5-6 chiffres ou 1-2 lettres suivies de 5-6 chiffres (Fédération Française de Course d'Orientation)"}</p>
                            <InputError message={errors.license_number} className="mt-2" />
                        </div>

                        <div className="mt-6 flex justify-end">
                            <PrimaryButton disabled={processing} className="bg-emerald-600 hover:bg-emerald-500">
                                {messages['modal.complete_profile.submit'] || "Terminer l'inscription"}
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
        </>
    );
}
