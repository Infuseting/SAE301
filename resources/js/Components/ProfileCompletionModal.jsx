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
    });

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
                    Finaliser votre inscription
                </h2>

                <p className="mt-1 text-sm text-gray-600 mb-6">
                    Veuillez compléter vos informations personnelles pour continuer.
                </p>

                <form onSubmit={submit}>
                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="birth_date" value="Date de naissance *" />
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
                            <InputLabel htmlFor="address" value="Adresse complète *" />
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
                            <InputLabel htmlFor="phone" value="Numéro de téléphone *" />
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
                            <InputLabel htmlFor="license_number" value="Numéro de licence (Facultatif)" />
                            <TextInput
                                id="license_number"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.license_number}
                                onChange={(e) => setData('license_number', e.target.value)}
                                placeholder="Laisser vide si vous n'en avez pas"
                            />
                            <InputError message={errors.license_number} className="mt-2" />
                        </div>

                        <div className="mt-6 flex justify-end">
                            <PrimaryButton disabled={processing} className="bg-emerald-600 hover:bg-emerald-500">
                                Terminer l'inscription
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
